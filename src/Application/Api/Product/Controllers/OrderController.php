<?php

namespace Application\Api\Product\Controllers;

use Application\Api\Product\Requests\CheckDiscountCodeRequest;
use Application\Api\Product\Requests\CheckOrderCodeRequest;
use Application\Api\Product\Requests\OrderRequest;
use Application\Api\Product\Requests\PaymentRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Order;
use Domain\Product\Repositories\Contracts\IOrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;


class OrderController extends Controller
{
    /**
     * Constructor of OrderController.
     */
    public function __construct(protected IOrderRepository $repository)
    {
        //
    }

    /**
     * Get all orders with pagination.
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get the order details.
     * @param Order $order
     * @return JsonResponse
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json($this->repository->show($order), Response::HTTP_OK);
    }

    /**
     * Store a new order.
     * @param OrderRequest $request
     * @return JsonResponse
     */
    public function store(OrderRequest $request): JsonResponse
    {
        return $this->repository->store($request);
    }

    /**
     * Check the order status.
     * @param CheckOrderCodeRequest $request
     * @return JsonResponse
     */
    public function checkOrderStatus(CheckOrderCodeRequest $request): JsonResponse
    {
        return response()->json($this->repository->checkOrderStatus($request), Response::HTTP_OK);
    }

    /**
     * Check the discount.
     * @param Order $order
     * @return JsonResponse
     */
    public function checkDiscount(Order $order, CheckDiscountCodeRequest $request): JsonResponse
    {
        return response()->json($this->repository->checkDiscount($order, $request->input('discount_code')), Response::HTTP_OK);
    }

    /**
     * Pay the order.
     * @param Order $order
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function payOrder(Order $order, PaymentRequest $request): JsonResponse
    {
        return $this->repository->payOrder($order, $request);
    }
}
