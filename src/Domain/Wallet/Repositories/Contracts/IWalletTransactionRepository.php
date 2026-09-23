<?php

namespace Domain\Wallet\Repositories\Contracts;

use Core\Http\Requests\TableRequest;
use Domain\Wallet\Models\Wallet;
use Illuminate\Pagination\LengthAwarePaginator;

interface IWalletTransactionRepository
{
    /**
     * Get the Wallet pagination.
     */
    public function index(TableRequest $request, Wallet $wallet): LengthAwarePaginator;
}
