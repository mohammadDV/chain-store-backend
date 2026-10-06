<?php

namespace Domain\Wallet\Repositories;

use Application\Api\Wallet\Requests\TopUpRequest;
use Application\Api\Wallet\Requests\TransferRequest;
use Application\Api\Wallet\Requests\WithdrawRequest;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Notification\Services\NotificationService;
use Domain\Payment\Models\Transaction;
use Domain\Payment\Services\PaymentGatewayAvailability;
use Domain\User\Models\User;
use Domain\User\Services\TelegramNotifier;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Models\WithdrawalTransaction;
use Domain\Wallet\Repositories\Contracts\IWalletRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletRepository implements IWalletRepository
{
    use GlobalFunc;

    public function __construct(
        protected TelegramNotifier $telegramNotifier,
        protected PaymentGatewayAvailability $paymentGateway,
    ) {}

    /**
     * Get the Wallet pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');

        return Wallet::query()
            ->when(Auth::user()->level != 3, function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
            ->when(! empty($search), function ($query) use ($search) {
                return $query->where('currency', 'like', '%'.$search.'%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));
    }

    /**
     * TopUp the balance
     */
    public function topUp(TopUpRequest $request)
    {
        if (! $this->paymentGateway->isEnabled()) {
            return $this->paymentGateway->disabledJsonResponse();
        }

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

        $code = Transaction::generateHash((string) $transaction->id);

        return [
            'status' => 1,
            'url' => route('user.payment').'?transaction='.$transaction->id.'&sign='.$code,
        ];
    }

    /**
     * Complete the topup
     */
    public function completeTopUp(int $walletTransactionId): void
    {
        try {
            $walletTransaction = DB::transaction(function () use ($walletTransactionId) {
                $walletTransaction = WalletTransaction::query()
                    ->lockForUpdate()
                    ->find($walletTransactionId);

                if (! $walletTransaction || $walletTransaction->status === WalletTransaction::COMPLETED) {
                    return null;
                }

                $claimed = WalletTransaction::query()
                    ->where('id', $walletTransaction->id)
                    ->where('status', '!=', WalletTransaction::COMPLETED)
                    ->update(['status' => WalletTransaction::COMPLETED]);

                if ($claimed === 0) {
                    return null;
                }

                $wallet = Wallet::query()
                    ->whereKey($walletTransaction->wallet_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->incrementBalance($wallet, (float) $walletTransaction->amount);

                NotificationService::create([
                    'title' => __('site.wallet_topup_title'),
                    'content' => __('site.wallet_topup_content'),
                    'id' => $wallet->id,
                    'type' => NotificationService::WALLET,
                ], $wallet->user);

                return $walletTransaction->fresh(['wallet.user']);
            });

            if (! $walletTransaction) {
                return;
            }

            $this->telegramNotifier->notifyOrder(
                'افزایش موجودی حساب'.PHP_EOL.
                'id '.$walletTransaction->wallet->id.PHP_EOL.
                'nickname '.$walletTransaction->wallet->user->nickname.PHP_EOL.
                'amount '.$walletTransaction->amount.PHP_EOL.
                'time '.now()
            );
        } catch (\Exception $e) {
            Log::error('Wallet top-up completion failed: '.$e->getMessage());
        }
    }

    /**
     * Transfer the balance to a wallet.
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        $senderWallet = $this->findByUserId(Auth::id());
        $recipient = User::query()
            ->where('customer_number', $request->input('customer_number'))
            ->where('id', '!=', Auth::id())
            ->firstOrFail();

        $recipientWallet = $this->findByUserId($recipient->id);
        $amount = (float) $request->input('amount');

        try {
            return DB::transaction(function () use ($request, $senderWallet, $recipient, $recipientWallet, $amount) {
                $walletIds = [$senderWallet->id, $recipientWallet->id];
                sort($walletIds);

                $lockedWallets = Wallet::query()
                    ->whereIn('id', $walletIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $lockedSender = $lockedWallets->get($senderWallet->id);
                $lockedRecipient = $lockedWallets->get($recipientWallet->id);

                if (! $lockedSender || ! $lockedRecipient || ! $lockedSender->canWithdraw($amount)) {
                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Insufficient funds'),
                    ], 422);
                }

                $senderTransaction = WalletTransaction::createTransaction(
                    wallet: $lockedSender,
                    amount: -$amount,
                    type: WalletTransaction::TRANSFER,
                    description: $request->description ?? __('site.wallet_transaction_transfer_to', ['email' => $recipient->email, 'wallet_id' => $lockedRecipient->id]),
                );

                WalletTransaction::createTransaction(
                    wallet: $lockedRecipient,
                    amount: $amount,
                    type: WalletTransaction::TRANSFER,
                    description: __('site.wallet_transaction_transfer_from', ['sender_number' => Auth::user()->customer_number, 'recipient_number' => $recipient->customer_number, 'transaction_id' => $senderTransaction->id]),
                );

                NotificationService::create([
                    'title' => __('site.wallet_transfer_title'),
                    'content' => __('site.wallet_transfer_content', ['user_nickname' => Auth::user()->nickname]),
                    'id' => $lockedRecipient->id,
                    'type' => NotificationService::WALLET,
                ], $recipient);

                $lockedSender->refresh();

                return response()->json([
                    'status' => 1,
                    'message' => __('site.Transfer successful'),
                    'data' => [
                        'transaction_reference' => $senderTransaction->reference,
                        'new_balance' => $lockedSender->balance,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Wallet transfer failed: '.$e->getMessage());

            return response()->json([
                'status' => 0,
                'message' => __('site.Transfer failed. Please try again.'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * find the wallet By User Id
     *
     * @param  $currency  = 'IRR'
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
     *
     * @param  Model  $model
     */
    public function update($model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Increment the balance
     */
    public function incrementBalance(Wallet $wallet, float $amount): bool
    {
        $wallet->increment('balance', $amount);

        return true;
    }

    /**
     * Decrement the balance
     */
    public function decrementBalance(Wallet $wallet, float $amount): bool
    {
        $wallet->decrement('balance', $amount);

        return true;
    }

    /**
     * Decrement the balance
     */
    public function getAvailableBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    /**
     * Withdraw from the wallet.
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $wallet = $this->findByUserId(Auth::id());
        $amount = $request->amount;
        $description = $request->description ?? 'Wallet withdrawal';

        try {
            return DB::transaction(function () use ($request, $wallet, $amount, $description) {
                $lockedWallet = Wallet::query()
                    ->whereKey($wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedWallet->canWithdraw($amount)) {
                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Insufficient funds'),
                    ], 422);
                }

                $transaction = WalletTransaction::createTransaction(
                    wallet: $lockedWallet,
                    amount: -$amount,
                    type: WalletTransaction::WITHDRAWAL,
                    description: $description,
                    status: WalletTransaction::COMPLETED
                );

                WithdrawalTransaction::create([
                    'wallet_id' => $lockedWallet->id,
                    'amount' => $amount,
                    'currency' => $lockedWallet->currency,
                    'status' => WithdrawalTransaction::PENDING,
                    'reference' => WithdrawalTransaction::generateReference(),
                    'description' => $description,
                    'card' => $request->card,
                    'sheba' => $request->sheba,
                ]);

                $lockedWallet->refresh();

                return response()->json([
                    'status' => 1,
                    'message' => __('site.Withdrawal successful'),
                    'data' => [
                        'transaction_reference' => $transaction->reference,
                        'new_balance' => $lockedWallet->balance,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Wallet withdrawal failed: '.$e->getMessage());

            return response()->json([
                'status' => 0,
                'message' => __('site.Withdrawal failed. Please try again.'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
