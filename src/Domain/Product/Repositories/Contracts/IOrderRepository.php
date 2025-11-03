<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\CheckOrderCodeRequest;
use Application\Api\Product\Requests\OrderRequest;
use Application\Api\Product\Requests\PaymentRequest;
use Application\Api\Product\Resources\OrderResource;
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
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the order details.
     * @param Order $order
     * @return OrderResource
     */
    public function show(Order $order): OrderResource;

    /**
     * Store a new order.
     * @param OrderRequest $request
     * @return JsonResponse
     */
    public function store(OrderRequest $request): JsonResponse;

    /**
     * Check the order status.
     * @param CheckOrderCodeRequest $request
     * @return array
     */
    public function checkOrderStatus(CheckOrderCodeRequest $request): array;

    /**
     * Check the discount.
     * @param Order $order
     * @param string $discountCode
     * @return array
     */
    public function checkDiscount(Order $order, string $discountCode): array;

    /**
     * Pay the order.
     * @param Order $order
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function payOrder(Order $order, PaymentRequest $request): JsonResponse;
}
