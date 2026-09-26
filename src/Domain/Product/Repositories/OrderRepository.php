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
use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Models\Discount;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IOrderRepository;
use Domain\Product\Services\StockService;
use Domain\Setting\Services\SettingService;
use Domain\User\Services\TelegramNotificationService;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Repositories\Contracts\IWalletRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class OrderRepository.
 */
class OrderRepository implements IOrderRepository
{
    use GlobalFunc;

    public function __construct(
        protected TelegramNotificationService $service,
        protected IWalletRepository $walletRepository,
        protected SettingService $settingService,
        protected StockService $stockService
    ) {
        //
    }

    /**
     * Get all orders with pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $status = $request->get('status');

        $orders = Order::query()
            ->with(['products', 'user', 'discount'])
            ->where('user_id', Auth::user()->id)
            ->where('active', 1)
            ->when(! empty($search), function ($query) use ($search) {
                return $query->where('id', 'like', '%'.$search.'%');
            })
            ->when(! empty($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $orders->through(fn ($order) => new OrderResource($order));
    }

    /**
     * Get the order details.
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
     */
    public function checkOrderStatus(CheckOrderCodeRequest $request): array
    {
        $order = Order::query()
            ->where('user_id', Auth::user()->id)
            ->where('code', $request->input('code'))
            ->where('active', 1)
            ->first();

        if (! $order) {
            return [
                'status' => 0,
                'message' => __('site.Order not found'),
            ];
        }

        return [
            'status' => 1,
            'order' => new OrderResource($order->load('products.color')),
            'message' => __('site.The operation has been successfully'),
        ];
    }

    /**
     * Check the discount.
     */
    public function checkDiscount(Order $order, ?string $discountCode): array
    {

        if ($order->user_id != Auth::user()->id || $order->status != Order::PENDING
            || $order->active != 1) {
            return [
                'status' => 0,
                'message' => __('site.Order not found'),
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
                    'message' => __('site.Discount already used'),
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
                    'message' => __('site.amount should grater than default amount', ['amount' => number_format(config('product.default_limit_discount_amount'))]),
                ];
            }

            return [
                'status' => 1,
                'amount' => $order->amount,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'delivery_amount' => $deliveryAmount,
                'discount_id' => $discount->id,
                'message' => __('site.The operation has been successfully'),
            ];
        }

        return [
            'status' => 0,
            'message' => __('site.Discount not found'),
        ];
    }

    /**
     * Store a new order.
     *
     * @throws \Exception
     */
    public function store(OrderRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $products = $request->input('products');

            $productIds = collect($products)->pluck('id')->unique()->all();
            $productModels = Product::query()
                ->with(['brand', 'sizes'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            // Calculate total amount
            $productsAmount = 0;
            $productCount = 0;

            foreach ($products as $productData) {
                $product = $productModels->get($productData['id']);

                if (! $product || $product->amount < (int) config('product.min_order_amount', 50000)) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Product not found'),
                    ], Response::HTTP_NOT_FOUND);
                }

                $productAmount = $product->amount;

                // Apply product discount if available
                // if ($product->discount > 0) {
                //     $productAmount = $productAmount - ($productAmount * $product->discount / 100);
                // }

                $productsAmount += $productAmount * $productData['count'];
                $productCount += $productData['count'];
            }

            $deliveryAmount = 0;

            if ($productsAmount < config('product.default_limit_delivery_amount')) {
                $deliveryAmount = config('product.default_delivery_amount');
            }

            // Calculate final total
            $totalAmount = $productsAmount + $deliveryAmount;

            $profitRate = $this->settingService->getProfitRateWithFallback();

            // Create order
            $order = Order::updateOrCreate([
                'user_id' => Auth::user()->id,
                'status' => Order::PENDING,
            ], [
                'product_count' => $productCount,
                'amount' => $productsAmount,
                'total_amount' => $totalAmount,
                'discount_amount' => 0,
                'delivery_amount' => $deliveryAmount,
                'discount_id' => null,
                'active' => 1,
                'vip' => 0,
                'code' => Order::generateCode(),
                'expire_date' => now()->addMinutes(30),
                'profit_rate' => $profitRate,
                'profit' => ($productsAmount * $profitRate / 100) + $deliveryAmount,
                'exchange_rate' => $this->settingService->getExchangeRateWithFallback(),
            ]);

            // Detach old products
            $order->products()->detach();

            // Attach products to order
            foreach ($products as $productData) {
                $product = $productModels->get($productData['id']);
                $productAmount = $product->amount;

                // if ($product->discount > 0) {
                //     $productAmount = $productAmount - ($productAmount * $product->discount / 100);
                // }

                if ($productData['size_id']) {
                    $sizeBelongsToProduct = $product->sizes->contains('id', (int) $productData['size_id']);

                    if (! $sizeBelongsToProduct) {
                        DB::rollBack();

                        return response()->json([
                            'status' => 0,
                            'message' => __('site.Insufficient stock'),
                        ], Response::HTTP_BAD_REQUEST);
                    }

                    $withPendingReservation = ! empty($product->brand->has_stock_management);
                    $availableStock = $this->stockService->availableForOrder(
                        (int) $productData['size_id'],
                        $withPendingReservation
                    );

                    if ($availableStock < $productData['count']) {
                        DB::rollBack();

                        return response()->json([
                            'status' => 0,
                            'message' => __('site.Insufficient stock'),
                        ], Response::HTTP_BAD_REQUEST);
                    }
                } else {
                    DB::rollBack();

                    return response()->json([
                        'status' => 0,
                        'message' => __('site.Insufficient stock'),
                    ], Response::HTTP_BAD_REQUEST);
                }

                $order->products()->attach($productData['id'], [
                    'count' => $productData['count'],
                    'amount' => $productAmount,
                    'status' => Order::PENDING,
                    'color_id' => $product->color_id ?? null,
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
                'order' => new OrderResource($order->load('products.color')),
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Paid an order.
     */
    public function payOrder(Order $order, PaymentRequest $request): JsonResponse
    {

        if ($order->status == Order::EXPIRED) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Order expired'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->checkLevelAccess(
            Auth::user()->id == $order->user_id &&
            $order->status == Order::PENDING
        );

        $amount = $order->amount;
        $discountAmount = 0;
        $deliveryAmount = $order->delivery_amount;
        $discountId = null;

        if (! empty($request->input('discount_code'))) {

            $calclulatedAmount = $this->checkDiscount($order, $request->input('discount_code'));

            if (empty($calclulatedAmount['status'])) {
                return response()->json([
                    'status' => 0,
                    'message' => $calclulatedAmount['message'],
                ], Response::HTTP_BAD_REQUEST);
            }

            $amount = $calclulatedAmount['amount'];
            $totalAmount = $calclulatedAmount['total_amount'];
            $discountAmount = $calclulatedAmount['discount_amount'];
            $deliveryAmount = $calclulatedAmount['delivery_amount'];
            $discountId = $calclulatedAmount['discount_id'];

        } else {
            $deliveryAmount = 0;

            if ($amount < config('product.default_limit_delivery_amount')) {
                $deliveryAmount = config('product.default_delivery_amount');
            }

            $totalAmount = $amount + $deliveryAmount;
        }

        $profitRate = $this->settingService->getProfitRateWithFallback();

        $order->update([
            'description' => $request->input('description'),
            'amount' => $amount,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'delivery_amount' => $deliveryAmount,
            'discount_id' => $discountId,
            'fullname' => $request->input('fullname'),
            'address' => $request->input('address'),
            'postal_code' => $request->input('postal_code'),
            'profit_rate' => $profitRate,
            'profit' => ($amount * $profitRate / 100) + $deliveryAmount - $discountAmount,
            'exchange_rate' => $this->settingService->getExchangeRateWithFallback(),
        ]);

        if ($request->input('payment_method') === Transaction::WALLET) {
            return $this->payWithWallet($order);
        }

        return $this->payWithBank($order);

    }

    /**
     * Pay with wallet.
     *
     * @throws \Exception
     */
    private function payWithWallet(Order $order): JsonResponse
    {
        DB::beginTransaction();

        try {

            $wallet = $this->walletRepository->findByUserId(Auth::id());

            $amount = $order->total_amount;

            if ($wallet->balance < $amount) {
                DB::rollBack();

                return response()->json([
                    'status' => 0,
                    'message' => __('site.Insufficient funds'),
                ], Response::HTTP_PAYMENT_REQUIRED);
            }

            // Get the wallet of the user that created this project
            $wallet = Wallet::query()
                ->where('currency', Wallet::IRR)
                ->where('user_id', Auth::user()->id)
                ->firstOrFail();

            // Update order status
            $order->update(['status' => Order::PAID]);

            $this->decrementStockForPaidOrder($order);

            WalletTransaction::createTransaction(
                $wallet,
                -$amount,
                WalletTransaction::PURCHASE,
                __('site.wallet_transaction_payment_order', ['order_id' => $order->code])
            );

            NotificationService::create([
                'title' => __('site.order_paid_title'),
                'content' => __('site.order_paid_content', ['order_code' => $order->code]),
                'id' => $order->id,
                'type' => NotificationService::ORDER,
            ], $order->user);

            DB::commit();

            $this->queueScraperRefreshForUnmanagedProducts($order);

            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully'),
                'order' => new OrderResource($order->load('products.color')),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Pay with bank.
     *
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

        $code = Transaction::generateHash((string) $transaction->id);

        return response()->json([
            'status' => 1,
            'message' => __('site.The operation has been successfully'),
            'url' => route('user.payment').'?transaction='.$transaction->id.'&sign='.$code,
        ], Response::HTTP_OK);
    }

    /**
     * Complete the order
     */
    public function completeOrder(int $orderId): void
    {
        try {
            $order = DB::transaction(function () use ($orderId) {
                $order = Order::query()
                    ->with(['products.brand', 'user'])
                    ->find($orderId);

                if (! $order || $order->status === Order::PAID) {
                    return null;
                }

                $claimed = Order::query()
                    ->where('id', $order->id)
                    ->where('status', '!=', Order::PAID)
                    ->update(['status' => Order::PAID]);

                if ($claimed === 0) {
                    return null;
                }

                $order->refresh();
                $order->load(['products.brand', 'user']);

                $this->decrementStockForPaidOrder($order);

                NotificationService::create([
                    'title' => __('site.order_paid_title'),
                    'content' => __('site.order_paid_content', ['order_code' => $order->code]),
                    'id' => $order->id,
                    'type' => NotificationService::ORDER,
                ], $order->user);

                return $order;
            });

            if (! $order) {
                return;
            }

            $this->queueScraperRefreshForUnmanagedProducts($order);

            $this->service->sendNotification(
                config('telegram.chat_id'),
                'سفارش با موفقیت پرداخت شد'.PHP_EOL.
                'order_id '.$order->id.PHP_EOL.
                'order_code '.$order->code.PHP_EOL.
                'order_amount '.$order->total_amount.PHP_EOL.
                'order_time '.now()
            );
        } catch (\Exception $e) {
            Log::error('Order completion failed: '.$e->getMessage());
        }
    }

    /**
     * Hard-decrement stock and write a Sale inventory transaction for every paid line.
     * Must be called inside an open DB transaction.
     */
    private function decrementStockForPaidOrder(Order $order): void
    {
        $order->loadMissing(['products']);

        foreach ($order->products as $product) {
            if (! $product->pivot->size_id) {
                continue;
            }

            $this->stockService->decrementForOrder(
                (int) $product->pivot->size_id,
                (int) $product->pivot->count,
                $order->user_id,
                'Order '.$order->code,
            );
        }
    }

    /**
     * Re-scrape products from brands that do not manage stock locally,
     * so quantity is synced from the upstream catalog after a sale.
     */
    private function queueScraperRefreshForUnmanagedProducts(Order $order): void
    {
        $order->loadMissing(['products.brand']);

        $queue = (string) config('product_scraper.cart_refresh.queue', 'high');
        $queued = [];

        foreach ($order->products as $product) {
            if (! empty($product->brand?->has_stock_management)) {
                continue;
            }

            $productId = (int) $product->id;
            if ($productId < 1 || isset($queued[$productId])) {
                continue;
            }

            $queued[$productId] = true;

            RefreshProductOnCartJob::dispatch($productId)->onQueue($queue);

            Log::info('OrderRepository: queued scraper refresh after purchase', [
                'order_id' => $order->id,
                'product_id' => $productId,
                'queue' => $queue,
            ]);
        }
    }

    /**
     * Expire pending orders whose expire_date has passed.
     *
     * @return int Number of expired orders
     */
    public function expirePendingOrders(): int
    {
        $expiredCount = Order::query()
            ->whereDoesntHave('transactions', function ($query) {
                $query->where('status', Transaction::PENDING)
                    ->where('created_at', '>=', now()->subMinutes(15));
            })
            ->where('status', Order::PENDING)
            ->where('active', 1)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<=', now())
            ->update(['status' => Order::EXPIRED]);

        return $expiredCount;
    }
}
