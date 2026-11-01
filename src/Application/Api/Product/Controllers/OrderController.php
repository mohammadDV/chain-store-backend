<?php

namespace Application\Api\Product\Controllers;

use Application\Api\Product\Requests\CheckDiscountCodeRequest;
use Application\Api\Product\Requests\CheckOrderCodeRequest;
use Application\Api\Product\Requests\OrderRequest;
use Application\Api\Product\Requests\PaymentRequest;
use Application\Api\Product\Requests\UnlockOrderRecipientRequest;
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
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get the order details.
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json($this->repository->show($order), Response::HTTP_OK);
    }

    /**
     * Public order lookup by tracking code (invoice QR).
     */
    public function showByCode(string $code): JsonResponse
    {
        $order = $this->repository->findByCode($code);

        if (! $order) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Order not found'),
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 1,
            'data' => $order,
            'message' => __('site.The operation has been successfully'),
        ], Response::HTTP_OK);
    }

    /**
     * Unlock full order details for invoice QR (shared delivery password).
     */
    public function unlockRecipient(string $code, UnlockOrderRecipientRequest $request): JsonResponse
    {
        $result = $this->repository->unlockRecipientByCode(
            $code,
            $request->input('password'),
            $request->input('unlock_token'),
        );

        $httpStatus = match ($result['code'] ?? null) {
            'order_not_found' => Response::HTTP_NOT_FOUND,
            'invoice_unlock_password_not_configured' => Response::HTTP_SERVICE_UNAVAILABLE,
            'invoice_unlock_token_invalid', 'invoice_unlock_password_invalid' => Response::HTTP_UNAUTHORIZED,
            default => $result['status'] === 1
                ? Response::HTTP_OK
                : Response::HTTP_UNAUTHORIZED,
        };

        return response()->json($result, $httpStatus);
    }

    /**
     * Store a new order.
     */
    public function store(OrderRequest $request): JsonResponse
    {
        return $this->repository->store($request);
    }

    /**
     * Check the order status.
     */
    public function checkOrderStatus(CheckOrderCodeRequest $request): JsonResponse
    {
        return response()->json($this->repository->checkOrderStatus($request), Response::HTTP_OK);
    }

    /**
     * Check the discount.
     */
    public function checkDiscount(Order $order, CheckDiscountCodeRequest $request): JsonResponse
    {
        return response()->json($this->repository->checkDiscount($order, $request->input('discount_code')), Response::HTTP_OK);
    }

    /**
     * Pay the order.
     */
    public function payOrder(Order $order, PaymentRequest $request): JsonResponse
    {
        return $this->repository->payOrder($order, $request);
    }
}
