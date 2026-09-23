<?php

namespace Domain\Wallet\Repositories\Contracts;

use Application\Api\Wallet\Requests\WithdrawalStatusRequest;
use Application\Api\Wallet\Requests\WithdrawRequest;
use Core\Http\Requests\TableRequest;
use Domain\Wallet\Models\WithdrawalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

interface IWithdrawalTransactionRepository
{
    /**
     * Get the WalletTransaction pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Withdraw from the wallet.
     */
    public function store(WithdrawRequest $request): JsonResponse;

    /**
     * Update the status of a withdrawal transaction.
     */
    public function updateStatus(WithdrawalTransaction $withdrawalTransaction, WithdrawalStatusRequest $request): JsonResponse;
}
