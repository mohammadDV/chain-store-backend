<?php

namespace Domain\Wallet\Repositories\Contracts;

use Application\Api\Wallet\Requests\TopUpRequest;
use Application\Api\Wallet\Requests\TransferRequest;
use Application\Api\Wallet\Requests\WithdrawRequest;
use Core\Http\Requests\TableRequest;
use Domain\Wallet\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

interface IWalletRepository
{

    /**
     * Get the Wallet pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator;

    /**
     * Top up the balance for a wallet.
     *
     * @param TopUpRequest $request
     */
    public function topUp(TopUpRequest $request);

    /**
     * Transfer the balance to a wallet.
     *
     * @param TransferRequest $request
     * @return JsonResponse
     */
    public function transfer(TransferRequest $request): JsonResponse;

    /**
     * Update the wallet with the given data.
     *
     * @param Wallet $wallet
     * @param array $data
     * @return bool
     */
    public function update(Wallet $wallet, array $data): bool;

    /**
     * Increment the wallet balance by the given amount.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @return bool
     */
    public function incrementBalance(Wallet $wallet, float $amount): bool;

    /**
     * Decrement the wallet balance by the given amount.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @return bool
     */
    public function decrementBalance(Wallet $wallet, float $amount): bool;

    /**
     * Get the available balance of the wallet.
     *
     * @param Wallet $wallet
     * @return float
     */
    public function getAvailableBalance(Wallet $wallet): float;

    /**
     * find the wallet By User Id
     * @param int $user_id
     * @param $currency = 'IRR'
     * @return Wallet
     */
    public function findByUserId(?int $user_id, $currency = 'IRR'): Wallet;

    /**
     * Withdraw from the wallet.
     *
     * @param WithdrawRequest $request
     * @return JsonResponse
     */
    public function withdraw(WithdrawRequest $request): JsonResponse;
}