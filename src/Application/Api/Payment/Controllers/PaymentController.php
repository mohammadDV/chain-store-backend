<?php

namespace Application\Api\Payment\Controllers;

use Application\Api\Payment\Requests\ManualPaymentRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Notification\Services\NotificationService;
use Domain\Payment\Models\Transaction;
use Domain\Payment\Repositories\Contracts\IPaymentRepository;
use Domain\Product\Repositories\OrderRepository;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Repositories\WalletRepository;
use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Facades\Toman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class PaymentController extends Controller
{
    use GlobalFunc;

    public function __construct(
        protected IPaymentRepository $repository,
    ) {}

    /**
     * Get the transaction pagination.
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request));
    }

    /**
     * Manual payment.
     */
    public function manualPayment(ManualPaymentRequest $request)
    {
        return response()->json($this->repository->manualPayment($request));
    }

    /**
     * Display a listing of the resource.
     */
    public function payment(Request $request)
    {
        $code = Transaction::generateHash($request->input('transaction'));

        if ($code != $request->input('sign')) {
            return [
                'status' => 0,
                'message' => __('site.invalid_request'),
            ];
        }

        $transaction = Transaction::findOrfail($request->input('transaction'));

        $user = User::findOrfail($transaction->user_id);

        $tomanRequest = Toman::amount($transaction->amount)
            ->description('Subscribe the first plan')
            ->callback(route('user.payment.callback'))
            ->mobile($user->mobile)
            ->email($user->email)
            ->request();

        if ($tomanRequest->successful()) {

            $transaction->update([
                'bank_transaction_id' => $tomanRequest->transactionId(),
            ]);

            return $tomanRequest->pay(); // Redirect to payment URL
        }

        return Redirect::to('http://localhost:3000/payment/result/'.$request->input('transaction'));

    }

    /**
     * Handle payment callback
     */
    public function callback(CallbackRequest $request)
    {
        // Read the stubbed/validated bank transaction id from PendingRequest data.
        // Avoid transactionId() here: vendor @method PHPDoc types it as PendingRequest only.
        $bankTransactionId = (string) $request->getRawData('transactionId');

        $transaction = Transaction::where('bank_transaction_id', $bankTransactionId)->first();

        if ($transaction) {

            if ($transaction->status === Transaction::COMPLETED) {
                return Redirect::to('/payment/result/'.$bankTransactionId);
            }

            $payment = $request->amount($transaction->amount)->verify();

            if ($payment->successful()) {
                $referenceId = $payment->referenceId();

                // Bank paid after the pending link was cancelled (e.g. user paid with wallet).
                if ($transaction->status === Transaction::CANCELLED
                    && $transaction->model_type === Transaction::ORDER) {
                    try {
                        $this->creditCancelledBankPaymentToWallet($transaction, $referenceId);
                    } catch (\Throwable $e) {
                        report($e);
                    }

                    return Redirect::to('/payment/result/'.$bankTransactionId);
                }

                // Fulfill first; only then mark completed so a failed completion can retry.
                $handled = $this->processHandling($transaction);

                if ($handled) {
                    Transaction::query()
                        ->whereKey($transaction->id)
                        ->where('status', '!=', Transaction::COMPLETED)
                        ->update([
                            'reference' => $referenceId,
                            'message' => __('site.transaction_successful'),
                            'status' => Transaction::COMPLETED,
                        ]);
                }
            }

            if ($payment->alreadyVerified()) {

                return response()->json([
                    'status' => 0,
                    'messsage' => $payment->message(),
                    'reference' => $payment->referenceId(),
                ]);

            }

            if ($payment->failed()) {
                $transaction->update([
                    'message' => __('site.not_paid'),
                    'status' => Transaction::FAILED,
                    'description' => $payment->message(),
                ]);

            }
        }

        return Redirect::to('/payment/result/'.$bankTransactionId);
    }

    /**
     * @return bool True when the related domain action succeeded (or was already done).
     */
    private function processHandling(Transaction $transaction): bool
    {
        return match ($transaction->model_type) {
            Transaction::WALLET => $this->completeWalletTopUp($transaction),
            Transaction::ORDER => app(OrderRepository::class)->completeOrder((int) $transaction->model_id),
            default => false,
        };
    }

    private function completeWalletTopUp(Transaction $transaction): bool
    {
        try {
            app(WalletRepository::class)->completeTopUp($transaction->model_id);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * When a bank payment succeeds after the pending transaction was cancelled
     * (typically because the order was paid with wallet), return the bank amount to the wallet.
     */
    private function creditCancelledBankPaymentToWallet(Transaction $transaction, string $referenceId): void
    {
        DB::transaction(function () use ($transaction, $referenceId) {
            /** @var Transaction|null $locked */
            $locked = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status === Transaction::COMPLETED) {
                return;
            }

            $wallet = Wallet::query()
                ->where('user_id', $locked->user_id)
                ->where('currency', Wallet::IRR)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new \RuntimeException('IRR wallet not found for cancelled bank payment credit.');
            }

            $user = User::query()->find($locked->user_id);
            if (! $user) {
                throw new \RuntimeException('User not found for cancelled bank payment credit.');
            }

            $amount = (float) $locked->amount;

            WalletTransaction::createTransaction(
                wallet: $wallet,
                amount: $amount,
                type: WalletTransaction::DEPOSITE,
                description: __('site.order_bank_payment_returned_to_wallet', [
                    'order_id' => $locked->model_id,
                ]),
                status: WalletTransaction::COMPLETED,
            );

            NotificationService::create([
                'title' => __('site.order_bank_payment_returned_title'),
                'content' => __('site.order_bank_payment_returned_content', [
                    'amount' => number_format($amount, 2),
                    'currency' => __('site.currency'),
                    'order_id' => $locked->model_id,
                ]),
                'id' => $locked->model_id,
                'type' => NotificationService::ORDER,
            ], $user);

            $locked->update([
                'reference' => $referenceId,
                'message' => __('site.order_bank_payment_returned_to_wallet', [
                    'order_id' => $locked->model_id,
                ]),
                'status' => Transaction::COMPLETED,
            ]);
        });
    }

    /**
     * Get the transaction result.
     */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->repository->show($id));
    }
}
