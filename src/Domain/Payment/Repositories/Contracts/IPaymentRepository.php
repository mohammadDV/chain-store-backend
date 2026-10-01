<?php

namespace Domain\Payment\Repositories\Contracts;

use Core\Http\Requests\TableRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface IPaymentRepository
{
    /**
     * Get the transaction pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the transaction result.
     */
    public function show(string $bankTransactionId): array;
}
