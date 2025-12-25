<?php

namespace Domain\Wallet\Repositories;

use Application\Api\Wallet\Requests\WithdrawalStatusRequest;
use Application\Api\Wallet\Requests\WithdrawRequest;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Notification\Services\NotificationService;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Models\WithdrawalTransaction;
use Domain\Wallet\Repositories\Contracts\IWithdrawalTransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalTransactionRepository implements IWithdrawalTransactionRepository
{
    use GlobalFunc;

    /**
     * Get the WalletTransaction pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        $search = $request->get('query');
        $status = $request->get('status');

        // Get the wallet
        $wallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return WithdrawalTransaction::query()
            ->when(Auth::user()->level != 3, function ($query) use ($wallet) {
                return $query->where('wallet_id', $wallet->id);
            })
            ->when(!empty($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('description', 'like', '%' . $search . '%')
                    ->orWhere('card','like','%' . $search . '%')
                    ->orWhere('sheba','like','%' . $search . '%')
                    ->orWhere('reference','like','%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));
    }

    /**
     * Withdraw from the wallet.
     * @param WithdrawRequest $request
     * @return JsonResponse
     */
    public function store(WithdrawRequest $request): JsonResponse
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
                'message' => __('site.You must verify your account to withdraw from your wallet'),
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();
        try {

            $wallet = Wallet::query()
                    ->where('user_id', Auth::id())
                    ->where('currency', Wallet::IRR)
                    ->where('status', 1)
                    ->firstOrFail();

            $amount = $request->amount;
            $description = $request->description ?? __('site.wallet_transaction_wallet_withdrawal');

            if (!$wallet->canWithdraw($amount)) {
                return response()->json([
                    'status' => 0,
                    'message' => __('site.Insufficient funds'),
                ], 422);
            }

            $transaction = WalletTransaction::createTransaction(
                wallet: $wallet,
                amount: -$amount,
                type: WalletTransaction::WITHDRAWAL,
                description: $description,
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
            ], 500);
        }
    }

    /**
     * Withdraw from the wallet.
     * @param WithdrawRequest $request
     * @return JsonResponse
     */
    public function updateStatus(WithdrawalTransaction $withdrawalTransaction, WithdrawalStatusRequest $request): JsonResponse
    {
        if (
            Auth::user()->level != 3 ||
            $withdrawalTransaction->status != WithdrawalTransaction::PENDING
        ) {
            throw New \Exception('Unauthorized', 403);
        }

        $data = [
            'status' => $request->input('status'),
        ];

        if ($request->filled('reason')) {
            $data['reason'] = $request->input('reason');
        }

        if ($request->filled('image')) {
            $data['image'] = $request->input('image');
        }

        DB::beginTransaction();
        try {

            if ($request->input('status') == WithdrawalTransaction::REJECT) {
                WalletTransaction::createTransaction(
                    wallet: $withdrawalTransaction->wallet,
                    amount: $withdrawalTransaction->amount,
                    type: WalletTransaction::REFUND,
                    description: __('site.wallet_transaction_withdrawal_refund', ['reference' => $withdrawalTransaction->reference]),
                    status: WalletTransaction::COMPLETED
                );
            }

            $withdrawalTransaction->update($data);

            NotificationService::create([
                'title' => __('site.wallet_withdrawal_rejected_title'),
                'content' => __('site.wallet_withdrawal_rejected_content'),
                'id' => $withdrawalTransaction->id,
                'type' => NotificationService::WITHDRAWAL,
            ], $withdrawalTransaction->wallet->user);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => __('site.Status updated successfully'),
                'data' => $withdrawalTransaction->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet withdrawal failed: ' . $e->getMessage());

            return response()->json([
                'status' => 0,
                'message' => __('site.Withdrawal failed. Please try again.'),
            ], 500);
        }
    }
}
