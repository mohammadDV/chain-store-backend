<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Requests\CheckOrderCodeRequest;
use Application\Api\Product\Requests\OrderRequest;
use Application\Api\Product\Requests\PaymentRequest;
use Application\Api\Product\Resources\OrderResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Notification\Services\NotificationService;
use Domain\Payment\Models\Transaction;
use Domain\Product\Models\Address;
use Domain\Product\Models\Discount;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IOrderRepository;
use Domain\User\Services\TelegramNotificationService;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Repositories\Contracts\IWalletRepository;
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

    public function __construct(protected TelegramNotificationService $service, protected IWalletRepository $walletRepository)
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
            ->where('active', 1)
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
            ->where('active', 1)
            ->first();

        return new OrderResource($order->load('products.color'));
    }

    /**
     * Check the order status.
     * @param CheckOrderCodeRequest $request
     * @return array
     */
    public function checkOrderStatus(CheckOrderCodeRequest $request): array
    {
        $order = Order::query()
            ->where('user_id', Auth::user()->id)
            ->where('code', $request->input('code'))
            ->where('active', 1)
            ->first();

        if (!$order) {
            return [
                'status' => 0,
                'message' => __('site.Order not found')
            ];
        }

        return [
            'status' => 1,
            'order' => new OrderResource($order->load('products.color')),
            'message' => __('site.The operation has been successfully')
        ];
    }

    /**
     * Check the discount.
     * @param Order $order
     * @param ?string $discountCode
     * @return array
     */
    public function checkDiscount(Order $order, ?string $discountCode): array
    {

        if ($order->user_id != Auth::user()->id || $order->status != Order::PENDING
            || $order->active != 1) {
            return [
                'status' => 0,
                'message' => __('site.Order not found')
            ];
        }

        $discount = Discount::query()
            ->where('code', $discountCode)
            ->first();

        if ($discount && $discount->isValid()) {

            $orderExist = Order::query()
                ->where('user_id', Auth::user()->id)
                ->where('discount_id', $discount->id)
                ->where('id', '!=', $order->id)
                ->whereNotIn('status', [Order::CANCELLED, Order::REFUNDED, Order::FAILED, Order::EXPIRED])
                ->where('active', 1)
                ->exists();

            if ($orderExist) {
                return [
                    'status' => 0,
                    'message' => __('site.Discount already used')
                ];
            }

            $discountAmount = $discount->calculateDiscount($order->amount);

            $deliveryAmount = 0;

            if ($order->amount < config('product.default_limit_delivery_amount')) {
                $deliveryAmount = config('product.default_delivery_amount');
            }

            // Calculate final total
            $totalAmount = $order->amount - $discountAmount + $deliveryAmount;

            if ($totalAmount < config('product.default_limit_discount_amount')) {

                return [
                    'status' => 0,
                    'message' => __('site.amount should grater than default amount', ['amount' => number_format(config('product.default_limit_discount_amount'))])
                ];
            }

            return [
                'status' => 1,
                'amount' => $order->amount,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'delivery_amount' => $deliveryAmount,
                'discount_id' => $discount->id,
                'message' => __('site.The operation has been successfully')
            ];
        }

        return [
            'status' => 0,
            'message' => __('site.Discount not found')
        ];
    }

    /**
     * Store a new order.
     * @param OrderRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(OrderRequest $request): JsonResponse
    {

        DB::beginTransaction();

        try {
            $products = $request->input('products');

            // Calculate total amount
            $productsAmount = 0;
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

                $productsAmount += $productAmount * $productData['count'];
                $productCount += $productData['count'];
            }

            $deliveryAmount = 0;

            if ($productsAmount < config('product.default_limit_delivery_amount')) {
                $deliveryAmount = config('product.default_delivery_amount');
            }

            // Calculate final total
            $totalAmount = $productsAmount + $deliveryAmount;

            // Create order
            $order = Order::updateOrCreate([
                'user_id' => Auth::user()->id,
                'status' => Order::PENDING,
            ], [
                'product_count' => $productCount,
                'amount' => $productsAmount,
                'total_amount' => $totalAmount,
                'delivery_amount' => $deliveryAmount,
                'active' => 1,
                'vip' => 0,
                'code' => Order::generateCode(),
                'expire_date' => now()->addMinutes(30),
            ]);

            // Detach old products
            $order->products()->detach();

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
     * Paid an order.
     * @param Order $order
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function payOrder(Order $order, PaymentRequest $request): JsonResponse
    {
        $address = Address::query()
            ->where('user_id', Auth::user()->id)
            ->where('id', $request->input('address_id'))
            ->first();

        if (!$address) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Address not found')
            ], Response::HTTP_NOT_FOUND);
        }

        $this->checkLevelAccess(
            Auth::user()->id == $order->user_id &&
            $order->status == Order::PENDING
        );

        $amount = $order->amount;
        $totalAmount = $order->total_amount;
        $discountAmount = 0;
        $deliveryAmount = $order->delivery_amount;
        $discountId = null;

        if (!empty($request->input('discount_code'))) {

            $calclulatedAmount = $this->checkDiscount($order, $request->input('discount_code'));

            if (empty($calclulatedAmount['status'])) {
                return response()->json([
                    'status' => 0,
                    'message' => $calclulatedAmount['message']
                ], Response::HTTP_BAD_REQUEST);
            }

            $amount = $calclulatedAmount['amount'];
            $totalAmount = $calclulatedAmount['total_amount'];
            $discountAmount = $calclulatedAmount['discount_amount'];
            $deliveryAmount = $calclulatedAmount['delivery_amount'];
            $discountId = $calclulatedAmount['discount_id'];

        }

        $order->update([
            'address_id' => $address->id,
            'description' => $request->input('description'),
            'amount' => $amount,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'delivery_amount' => $deliveryAmount,
            'discount_id' => $discountId,
        ]);

        if ($request->input('payment_method') === Transaction::WALLET) {
            return $this->payWithWallet($order);
        }

        return $this->payWithBank($order);

    }

    /**
     * Pay with wallet.
     * @param Order $order
     * @return JsonResponse
     * @throws \Exception
     */
    private function payWithWallet(Order $order): JsonResponse
    {
        $wallet = $this->walletRepository->findByUserId(Auth::id());

        $amount = $order->total_amount;

        if ($wallet->balance < $amount) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Insufficient funds'),
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        try {
            DB::beginTransaction();

            // Get the wallet of the user that created this project
            $wallet = Wallet::query()
                ->where('currency', Wallet::IRR)
                ->where('user_id', Auth::user()->id)
                ->firstOrFail();

            // Update claim status
            $order->update(['status' => Order::PAID]);

            WalletTransaction::createTransaction(
                $wallet,
                -$amount,
                WalletTransaction::PURCHASE,
                __('site.wallet_transaction_payment_order', ['order_id' => $order->code])
            );

            NotificationService::create([
                'title' => __('site.claim_paid_title'),
                'content' => __('site.claim_paid_content', ['order_code' => $order->code]),
                'id' => $order->id,
                'type' => NotificationService::ORDER,
            ], $order->user);

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
     * Pay with bank.
     * @param Order $order
     * @throws \Exception
     */
    private function payWithBank(Order $order)
    {
        $amount = $order->total_amount;

        $transaction = Transaction::create([
            'status' => Transaction::PENDING,
            'model_id' => $order->id,
            'model_type' => Transaction::ORDER,
            'amount' => $amount,
            'user_id' => Auth::user()->id,
        ]);

        $code = Transaction::generateHash($transaction->id);

        if ($transaction) {
            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully'),
                'url' => route('user.payment') . '?transaction=' . $transaction->id . '&sign=' . $code
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => 0,
            'message' => __('site.Top-up failed. Please try again.'),
        ], 500);
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
        return response()->json([
            'status' => 1,
            'message' => __('site.The operation has been successfully'),
            'order' => new OrderResource($order->load('products.color'))
        ], Response::HTTP_OK);
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
