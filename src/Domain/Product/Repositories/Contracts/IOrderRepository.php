<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\CheckOrderCodeRequest;
use Application\Api\Product\Requests\OrderRequest;
use Application\Api\Product\Requests\PaymentRequest;
use Application\Api\Product\Resources\OrderResource;
use Application\Api\Product\Resources\PublicOrderResource;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IOrderRepository.
 */
interface IOrderRepository
{
    /**
     * Get all orders with pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the order details.
     */
    public function show(Order $order): OrderResource;

    /**
     * Public order lookup by tracking code (invoice QR).
     */
    public function findByCode(string $code): ?PublicOrderResource;

    /**
     * Unlock full order details for a public order code.
     *
     * @return array{status: int, message: string, code?: string, data?: array<string, mixed>}
     */
    public function unlockRecipientByCode(string $code, ?string $password, ?string $unlockToken): array;

    /**
     * Store a new order.
     */
    public function store(OrderRequest $request): JsonResponse;

    /**
     * Check the order status.
     */
    public function checkOrderStatus(CheckOrderCodeRequest $request): array;

    /**
     * Check the discount.
     */
    public function checkDiscount(Order $order, string $discountCode): array;

    /**
     * Pay the order.
     */
    public function payOrder(Order $order, PaymentRequest $request): JsonResponse;

    /**
     * Expire pending orders that have been created more than one hour ago.
     *
     * @return int Number of expired orders
     */
    public function expirePendingOrders(): int;
}
