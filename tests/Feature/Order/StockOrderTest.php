<?php

namespace Tests\Feature\Order;

use Application\Api\Product\Requests\PaymentRequest;
use Application\Api\Product\Resources\SizeResource;
use Domain\Brand\Models\Brand;
use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Repositories\OrderRepository;
use Domain\Product\Services\StockService;
use Domain\User\Models\User;
use Domain\User\Services\TelegramNotificationService;
use Domain\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class StockOrderTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);

        $telegram = Mockery::mock(TelegramNotificationService::class);
        $telegram->shouldReceive('sendNotification')->andReturnNull();
        $this->app->instance(TelegramNotificationService::class, $telegram);
    }

    /**
     * @return array{0: User, 1: Product, 2: Size}
     */
    private function seedProductWithStock(int $quantity, bool $hasStockManagement = true): array
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create([
            'has_stock_management' => $hasStockManagement ? 1 : 0,
        ]);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'user_id' => $user->id,
            'amount' => 100,
            'active' => 1,
            'status' => Product::COMPLETED,
            'is_failed' => 0,
        ]);
        $size = Size::factory()->create([
            'product_id' => $product->id,
            'status' => 1,
        ]);
        $this->stockService->setQuantity($size->id, $quantity);

        return [$user, $product, $size];
    }

    public function test_order_fails_when_stock_insufficient(): void
    {
        [$user, $product, $size] = $this->seedProductWithStock(2);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 5, 'size_id' => $size->id],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 0,
                'message' => __('site.Insufficient stock'),
            ]);
    }

    public function test_order_fails_without_size_id(): void
    {
        [$user, $product] = $this->seedProductWithStock(10);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 1, 'size_id' => null],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 0,
                'message' => __('site.Insufficient stock'),
            ]);
    }

    public function test_order_succeeds_when_stock_sufficient(): void
    {
        [$user, $product, $size] = $this->seedProductWithStock(10);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 2, 'size_id' => $size->id],
            ],
        ]);

        $response->assertCreated()
            ->assertJson(['status' => 1]);
        $this->assertSame(10, $size->stock->fresh()->quantity);
    }

    public function test_pending_orders_soft_reserve_when_stock_management_enabled(): void
    {
        [$user, $product, $size] = $this->seedProductWithStock(5, true);
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);
        $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 3, 'size_id' => $size->id],
            ],
        ])->assertCreated();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 3, 'size_id' => $size->id],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 0,
                'message' => __('site.Insufficient stock'),
            ]);
    }

    public function test_pending_orders_do_not_soft_reserve_without_stock_management(): void
    {
        [$user, $product, $size] = $this->seedProductWithStock(5, false);
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);
        $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 3, 'size_id' => $size->id],
            ],
        ])->assertCreated();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 3, 'size_id' => $size->id],
            ],
        ]);

        $response->assertCreated()->assertJson(['status' => 1]);
    }

    public function test_complete_order_decrements_stock_when_stock_management_enabled(): void
    {
        Queue::fake();

        [$user, $product, $size] = $this->seedProductWithStock(10, true);
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 4, 'size_id' => $size->id],
            ],
        ])->assertCreated();

        $order = Order::query()->where('user_id', $user->id)->where('status', Order::PENDING)->firstOrFail();

        app(OrderRepository::class)->completeOrder($order->id);

        $this->assertSame(6, $size->stock->fresh()->quantity);
        $this->assertSame(0, $size->stock->fresh()->reserved);
        $this->assertSame(Order::PAID, $order->fresh()->status);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Sale->value,
            'source' => InventoryTransactionSource::Order,
            'user_id' => $user->id,
            'quantity_change' => -4,
            'previous_quantity' => 10,
            'resulting_quantity' => 6,
        ]);
        Queue::assertNotPushed(RefreshProductOnCartJob::class);
    }

    public function test_wallet_payment_decrements_stock_and_writes_sale(): void
    {
        Queue::fake();

        [$user, $product, $size] = $this->seedProductWithStock(10, true);
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 99_999_999_999,
            'currency' => Wallet::IRR,
            'status' => 1,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 3, 'size_id' => $size->id],
            ],
        ])->assertCreated();

        $order = Order::query()->where('user_id', $user->id)->where('status', Order::PENDING)->firstOrFail();

        $paymentRequest = PaymentRequest::create(
            "/api/profile/orders/{$order->id}/pay",
            'POST',
            [
                'payment_method' => 'wallet',
                'address' => 'Test address',
                'postal_code' => '1234567890',
                'phone' => '09120000000',
            ]
        );
        $paymentRequest->setContainer($this->app);
        $paymentRequest->setRedirector($this->app->make('redirect'));
        // Bypass FormRequest validation; payOrder only reads inputs.

        $response = app(OrderRepository::class)->payOrder($order, $paymentRequest);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, $response->getData(true)['status']);
        $this->assertSame(Order::PAID, $order->fresh()->status);
        $this->assertSame(7, $size->stock->fresh()->quantity);
        $this->assertSame(
            1,
            InventoryTransaction::query()
                ->where('size_id', $size->id)
                ->where('type', InventoryTransactionType::Sale)
                ->count()
        );
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Sale->value,
            'source' => InventoryTransactionSource::Order,
            'quantity_change' => -3,
            'previous_quantity' => 10,
            'resulting_quantity' => 7,
        ]);
        Queue::assertNotPushed(RefreshProductOnCartJob::class);
    }

    public function test_complete_order_decrements_and_queues_scraper_without_stock_management(): void
    {
        Queue::fake();

        [$user, $product, $size] = $this->seedProductWithStock(10, false);
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/orders', [
            'products' => [
                ['id' => $product->id, 'count' => 4, 'size_id' => $size->id],
            ],
        ])->assertCreated();

        $order = Order::query()->where('user_id', $user->id)->where('status', Order::PENDING)->firstOrFail();
        app(OrderRepository::class)->completeOrder($order->id);

        $this->assertSame(6, $size->stock->fresh()->quantity);
        $this->assertSame(Order::PAID, $order->fresh()->status);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Sale->value,
            'source' => InventoryTransactionSource::Order,
            'quantity_change' => -4,
            'previous_quantity' => 10,
            'resulting_quantity' => 6,
        ]);
        Queue::assertPushedOn('high', RefreshProductOnCartJob::class, function (RefreshProductOnCartJob $job) use ($product) {
            return $job->productId === $product->id;
        });
    }

    public function test_size_resource_exposes_quantity_as_stock(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 42);
        $size->load('stock');

        $payload = (new SizeResource($size))->toArray(Request::create('/'));

        $this->assertSame(42, $payload['stock']);
        $this->assertSame($size->id, $payload['id']);
    }

    public function test_scope_active_requires_positive_stock_quantity(): void
    {
        $brand = Brand::factory()->create();
        $inStock = Product::factory()->create([
            'brand_id' => $brand->id,
            'active' => 1,
            'status' => Product::COMPLETED,
            'is_failed' => 0,
        ]);
        $outOfStock = Product::factory()->create([
            'brand_id' => $brand->id,
            'active' => 1,
            'status' => Product::COMPLETED,
            'is_failed' => 0,
        ]);

        $sizeIn = Size::factory()->create(['product_id' => $inStock->id, 'status' => 1]);
        $sizeOut = Size::factory()->create(['product_id' => $outOfStock->id, 'status' => 1]);
        $this->stockService->setQuantity($sizeIn->id, 5);
        $this->stockService->setQuantity($sizeOut->id, 0);

        $ids = Product::query()->active()->pluck('id')->all();

        $this->assertContains($inStock->id, $ids);
        $this->assertNotContains($outOfStock->id, $ids);
    }
}
