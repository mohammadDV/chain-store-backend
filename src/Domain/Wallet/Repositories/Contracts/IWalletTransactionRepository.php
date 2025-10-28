<?php

namespace Domain\Wallet\Repositories\Contracts;

use Core\Http\Requests\TableRequest;
use Domain\Wallet\Models\Wallet;
use Illuminate\Pagination\LengthAwarePaginator;

interface IWalletTransactionRepository
{

    /**
     * Get the Wallet pagination.
     * @param TableRequest $request
     * @param Wallet $wallet
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request, Wallet $wallet) :LengthAwarePaginator;
}