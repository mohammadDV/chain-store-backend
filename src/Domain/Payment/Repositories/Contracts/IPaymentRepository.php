<?php

namespace Domain\Payment\Repositories\Contracts;

use Application\Api\Payment\Requests\ManualPaymentRequest;
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

    /**
     * Manual payment.
     */
    public function manualPayment(ManualPaymentRequest $request): array;
}
