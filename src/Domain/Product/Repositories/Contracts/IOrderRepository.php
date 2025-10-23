<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\OrderRequest;
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
     * Update the order.
     * @param OrderRequest $request
     * @param Order $order
     * @return JsonResponse
     */
    public function update(OrderRequest $request, Order $order): JsonResponse;
}