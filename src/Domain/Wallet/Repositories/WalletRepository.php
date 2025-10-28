<?php

namespace Domain\Wallet\Repositories;

use Application\Api\Wallet\Requests\TopUpRequest;
use Application\Api\Wallet\Requests\TransferRequest;
use Application\Api\Wallet\Requests\WithdrawRequest;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Notification\Services\NotificationService;
use Domain\Payment\Models\Transaction;
use Domain\User\Models\User;
use Domain\User\Services\TelegramNotificationService;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Models\WithdrawalTransaction;
use Domain\Wallet\Repositories\Contracts\IWalletRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class WalletRepository implements IWalletRepository
{

    use GlobalFunc;

    public function __construct(protected TelegramNotificationService $service)
    {
        
    }

    /**
     * Get the Wallet pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        $search = $request->get('query');
        return Wallet::query()
            ->when(Auth::user()->level != 3, function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('currency', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));
    }

    /**
     * TopUp the balance
     * @param TopUpRequest $request
     */
    public function topUp(TopUpRequest $request)
    {
        if (empty(Auth::user()->status)) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty(Auth::user()->verified_at)) {
            return response()->json([
                'status' => 0,
                'message' => __('site.You must verify your account to top up your wallet'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get the wallet
        $wallet = $this->findByUserId(Auth::id());

        $amount = intval($request->input('amount'));

        // Create transaction record
        $walletTransaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => $amount,
            'currency' => Wallet::IRR,
            'status' => WalletTransaction::PENDING,
            'reference' => WalletTransaction::generateReference(),
            'description' => __('site.wallet_transaction_wallet_topup'),
        ]);

        $transaction = Transaction::create([
            'status' => Transaction::PENDING,
            'model_id' => $walletTransaction->id,
            'model_type' => Transaction::WALLET,
            'amount' => $amount + ($amount * config('fee.site') / 100),
            'user_id' => Auth::user()->id,
        ]);

        $code = Transaction::generateHash($transaction->id);

        if ($transaction) {
            return [
                'status' => 1,
                'url' => route('user.payment') . '?transaction=' . $transaction->id . '&sign=' . $code
            ];
        }

        return response()->json([
            'status' => 0,
            'message' => __('site.Top-up failed. Please try again.'),
        ], Response::HTTP_BAD_REQUEST);

    }

    /**
     * Complete the topup
     * @param int $walletTransactionId
     * @return void
     */
    public function completeTopUp(int $walletTransactionId) :void
    {
        try {
            DB::beginTransaction();

            // Create transaction record
            $walletTransaction = WalletTransaction::find($walletTransactionId);
            // Update wallet balance
            $this->incrementBalance($walletTransaction->wallet, $walletTransaction->amount);

            // Update transaction status
            $this->update($walletTransaction, ['status' => 'completed']);

            NotificationService::create([
                'title' => __('site.wallet_topup_title'),
                'content' => __('site.wallet_topup_content'),
                'id' => $walletTransaction->wallet->id,
                'type' => NotificationService::WALLET,
            ], $walletTransaction?->wallet?->user);

            DB::commit();

            $this->service->sendNotification(
                config('telegram.chat_id'),
                'افزایش موجودی حساب' . PHP_EOL .
                'id ' . $walletTransaction->wallet->id . PHP_EOL .
                'nickname ' . $walletTransaction->wallet->user->nickname . PHP_EOL .
                'amount ' . $walletTransaction->amount . PHP_EOL .
                'time ' . now()
            );

        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    /**
     * Transfer the balance to a wallet.
     *
     * @param TransferRequest $request
     * @return JsonResponse
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $senderWallet = $this->findByUserId(Auth::id());
            $recipient = User::query()
                ->where('customer_number', $request->input('customer_number'))
                ->where('id', '!=', Auth::id())
                ->firstOrFail();

            $recipientWallet = $this->findByUserId($recipient->id);

            if (!$senderWallet->canWithdraw($request->input('amount'))) {
                return response()->json([
                    'status' => 0,
                    'message' => __('site.Insufficient funds'),
                ], 422);
            }

            // Sender's transaction (Debit)
            $senderTransaction = WalletTransaction::createTransaction(
                wallet: $senderWallet,
                amount: -$request->input('amount'),
                type: WalletTransaction::TRANSFER,
                description: $request->description ?? __('site.wallet_transaction_transfer_to', ['email' => $recipient->email, 'wallet_id' => $recipientWallet->customer_number]),
            );

            // Recipient's transaction (Credit)
            WalletTransaction::createTransaction(
                wallet: $recipientWallet,
                amount: $request->input('amount'),
                type: WalletTransaction::TRANSFER,
                description: __('site.wallet_transaction_transfer_from', ['sender_number' => Auth::user()->customer_number, 'recipient_number' => $recipient->customer_number, 'transaction_id' => $senderTransaction->id]),
            );

            NotificationService::create([
                'title' => __('site.wallet_transfer_title'),
                'content' => __('site.wallet_transfer_content', ['user_nickname' => Auth::user()->nickname]),
                'id' => $recipientWallet->id,
                'type' => NotificationService::WALLET,
            ], $recipient);

            DB::commit();

            $senderWallet->refresh();

            return response()->json([
                'status' => 1,
                'message' => __('site.Transfer successful'),
                'data' => [
                    'transaction_reference' => $senderTransaction->reference,
                    'new_balance' => $senderWallet->balance,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet transfer failed: ' . $e->getMessage());

            return response()->json([
                'status' => 0,
                'message' => __('site.Transfer failed. Please try again.'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * find the wallet By User Id
     * @param int $user_id
     * @param $currency = 'IRR'
     * @return Wallet
     */
    public function findByUserId(?int $user_id, $currency = 'IRR'): Wallet
    {
        return Wallet::query()
                ->where('user_id', $user_id)
                ->where('currency', $currency)
                ->where('status', 1)
                ->firstOrFail();
    }

    /**
     * Update the model with the given data.
     * @param Model $model
     * @param array $data
     * @return bool
     */
    public function update($model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Increment the balance
     * @param Wallet $wallet
     * @param float $amount
     * @return bool
     */
    public function incrementBalance(Wallet $wallet, float $amount): bool
    {
        return $wallet->increment('balance', $amount);
    }

    /**
     * Decrement the balance
     * @param Wallet $wallet
     * @param float $amount
     * @return bool
     */
    public function decrementBalance(Wallet $wallet, float $amount): bool
    {
        return $wallet->decrement('balance', $amount);
    }

    /**
     * Decrement the balance
     * @param Wallet $wallet
     * @return float
     */
    public function getAvailableBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    /**
     * Withdraw from the wallet.
     * @param WithdrawRequest $request
     * @return JsonResponse
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $wallet = $this->findByUserId(Auth::id());
            $amount = $request->amount;
            $description = $request->description ?? 'Wallet withdrawal';

            if (!$wallet->canWithdraw($amount)) {
                return response()->json([
                    'status' => 0,
                    'message' => __('site.Insufficient funds'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $transaction = WalletTransaction::createTransaction(
                wallet: $wallet,
                amount: -$amount,
                type: WalletTransaction::WITHDRAWAL,
                description: $description ?? __('site.wallet_transaction_wallet_withdrawal'),
                status: WalletTransaction::COMPLETED
            );

            WithdrawalTransaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'currency' => $wallet->currency,
                'status' => WithdrawalTransaction::PENDING,
                'reference' => WithdrawalTransaction::generateReference(),
                'description' => $description,
                'card' => $request->card,
                'sheba' => $request->sheba,
            ]);

            DB::commit();

            $wallet->refresh();

            return response()->json([
                'status' => 1,
                'message' => __('site.Withdrawal successful'),
                'data' => [
                    'transaction_reference' => $transaction->reference,
                    'new_balance' => $wallet->balance,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet withdrawal failed: ' . $e->getMessage());

            return response()->json([
                'status' => 0,
                'message' => __('site.Withdrawal failed. Please try again.'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
