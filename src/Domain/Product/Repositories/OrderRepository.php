<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Requests\OrderRequest;
use Application\Api\Product\Resources\OrderResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Discount;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IOrderRepository;
use Domain\User\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


/**
 * Class OrderRepository.
 */
class OrderRepository implements IOrderRepository
{
    use GlobalFunc;

    public function __construct(protected TelegramNotificationService $service)
    {
        //
    }

    /**
     * Get all orders with pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $status = $request->get('status');

        $orders = Order::query()
            ->with(['products', 'user', 'discount'])
            ->where('user_id', Auth::user()->id)
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('id', 'like', '%' . $search . '%');
            })
            ->when(!empty($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $orders->through(fn ($order) => new OrderResource($order));
    }

    /**
     * Get the order details.
     * @param Order $order
     * @return OrderResource
     */
    public function show(Order $order): OrderResource
    {
        $this->checkLevelAccess(Auth::user()->id == $order->user_id);

        $order = Order::query()
            ->with(['products', 'discount'])
            ->where('id', $order->id)
            ->first();

        return new OrderResource($order->load('products.color'));
    }


    /**
     * Store a new order.
     * @param OrderRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(OrderRequest $request): JsonResponse
    {
        // Delete pending orders and their related products
        Order::query()
            ->where('user_id', Auth::user()->id)
            ->where('status', Order::PENDING)
            ->delete();

        DB::beginTransaction();

        try {
            $products = $request->input('products');
            $discountCode = $request->input('discount_code');

            // Calculate total amount
            $totalAmount = 0;
            $productCount = 0;

            foreach ($products as $productData) {
                $product = Product::find($productData['id']);

                if (!$product) {
                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Product not found')
                    ], Response::HTTP_NOT_FOUND);
                }

                $productAmount = $product->amount;

                // Apply product discount if available
                if ($product->discount > 0) {
                    $productAmount = $productAmount - ($productAmount * $product->discount / 100);
                }

                $totalAmount += $productAmount * $productData['count'];
                $productCount += $productData['count'];
            }

            // Apply discount code if provided
            $discountAmount = 0;
            $discountId = null;

            if (!empty($discountCode)) {
                $discount = Discount::where('code', $discountCode)->first();

                if ($discount && $discount->isValid()) {
                    $discountAmount = $discount->calculateDiscount($totalAmount);
                    $discountId = $discount->id;
                } else {
                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Invalid or expired discount code')
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $deliveryAmount = 0;

            if ($totalAmount < config('product.default_limit_delivery_amount')) {
                $deliveryAmount = config('product.default_delivery_amount');
            }

            // Calculate final total
            $finalTotal = $totalAmount - $discountAmount + $deliveryAmount;

            // Create order
            $order = Order::create([
                'user_id' => Auth::user()->id,
                'discount_id' => $discountId,
                'product_count' => $productCount,
                'total_amount' => $finalTotal,
                'delivery_amount' => $deliveryAmount,
                'discount_amount' => $discountAmount,
                'status' => Order::PENDING,
                'active' => 1,
                'vip' => 0,
                'expire_date' => now()->addMinutes(30),
            ]);

            // Attach products to order
            foreach ($products as $productData) {
                $product = Product::find($productData['id']);
                $productAmount = $product->amount;

                if ($product->discount > 0) {
                    $productAmount = $productAmount - ($productAmount * $product->discount / 100);
                }

                $order->products()->attach($productData['id'], [
                    'count' => $productData['count'],
                    'amount' => $productAmount,
                    'status' => Order::PENDING,
                    'color_id' => $productData['color_id'] ?? null,
                    'size_id' => $productData['size_id'] ?? null,
                ]);

                // Update product order count
                $product->increment('order_count', $productData['count']);
            }

            // Send notification
            // $this->service->sendNotification(
            //     config('telegram.chat_id'),
            //     'سفارش جدید' . PHP_EOL .
            //     'Order ID: ' . $order->id . PHP_EOL .
            //     'User: ' . Auth::user()->nickname . PHP_EOL .
            //     'Amount: ' . $finalTotal
            // );

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully'),
                'order' => new OrderResource($order->load('products.color'))
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update the order.
     * @param OrderRequest $request
     * @param Order $order
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(OrderRequest $request, Order $order): JsonResponse
    {
        $this->checkLevelAccess(Auth::user()->id == $order->user_id);

        DB::beginTransaction();

        try {
            $products = $request->input('products');
            $discountCode = $request->input('discount_code');
            $deliveryAmount = $request->input('delivery_amount', $order->delivery_amount);

            // Calculate total amount
            $totalAmount = 0;
            $productCount = 0;

            foreach ($products as $productData) {
                $product = Product::find($productData['id']);

                if (!$product) {
                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Product not found')
                    ], Response::HTTP_NOT_FOUND);
                }

                $productAmount = $product->amount;

                if ($product->discount > 0) {
                    $productAmount = $productAmount - ($productAmount * $product->discount / 100);
                }

                $totalAmount += $productAmount * $productData['count'];
                $productCount += $productData['count'];
            }

            // Apply discount code if provided
            $discountAmount = 0;
            $discountId = $order->discount_id;

            if ($discountCode) {
                $discount = Discount::where('code', $discountCode)->first();

                if ($discount && $discount->isValid()) {
                    $discountAmount = $discount->calculateDiscount($totalAmount);
                    $discountId = $discount->id;
                } else {
                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Invalid or expired discount code')
                    ], Response::HTTP_BAD_REQUEST);
                }
            } elseif ($order->discount_id) {
                // Keep existing discount
                $discount = $order->discount;
                if ($discount && $discount->isValid()) {
                    $discountAmount = $discount->calculateDiscount($totalAmount);
                }
            }

            // Calculate final total
            $finalTotal = $totalAmount - $discountAmount + $deliveryAmount;

            // Update order
            $order->update([
                'discount_id' => $discountId,
                'product_count' => $productCount,
                'total_amount' => $finalTotal,
                'delivery_amount' => $deliveryAmount,
                'discount_amount' => $discountAmount,
            ]);

            // Detach old products
            $order->products()->detach();

            // Attach new products
            foreach ($products as $productData) {
                $product = Product::find($productData['id']);
                $productAmount = $product->amount;

                if ($product->discount > 0) {
                    $productAmount = $productAmount - ($productAmount * $product->discount / 100);
                }

                $order->products()->attach($productData['id'], [
                    'count' => $productData['count'],
                    'amount' => $productAmount,
                    'status' => $order->status,
                    'color_id' => $productData['color_id'] ?? null,
                    'size_id' => $productData['size_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully'),
                'order' => new OrderResource($order->load('products'))
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
