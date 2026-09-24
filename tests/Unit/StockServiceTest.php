<?php

namespace Tests\Unit;

use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Models\Stock;
use Domain\Product\Services\StockService;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);
    }

    public function test_creating_size_auto_creates_stock_row(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);

        $this->assertDatabaseHas('stocks', [
            'size_id' => $size->id,
            'quantity' => 0,
            'reserved' => 0,
        ]);
        $this->assertTrue($size->stock()->exists());
    }

    public function test_deleting_size_cascades_stock(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $stockId = $size->stock->id;

        $size->delete();

        $this->assertDatabaseMissing('stocks', ['id' => $stockId]);
    }

    public function test_set_quantity_upserts_quantity_without_touching_reserved(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $size->stock->update(['reserved' => 3, 'quantity' => 0]);

        $stock = $this->stockService->setQuantity($size->id, 15);

        $this->assertSame(15, $stock->quantity);
        $this->assertSame(3, $stock->fresh()->reserved);
    }

    public function test_available_for_order_without_pending_reservation(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        DB::beginTransaction();
        $available = $this->stockService->availableForOrder($size->id, false);
        DB::commit();

        $this->assertSame(10, $available);
    }

    public function test_available_for_order_subtracts_pending_orders(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
            'active' => 1,
        ]);
        $order->products()->attach($product->id, [
            'count' => 4,
            'amount' => 100,
            'status' => Order::PENDING,
            'color_id' => null,
            'size_id' => $size->id,
        ]);

        DB::beginTransaction();
        $available = $this->stockService->availableForOrder($size->id, true);
        DB::commit();

        $this->assertSame(6, $available);
    }

    public function test_decrement_for_order_reduces_quantity(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        DB::beginTransaction();
        $this->stockService->decrementForOrder($size->id, 3);
        DB::commit();

        $this->assertSame(7, $size->stock->fresh()->quantity);
        $this->assertSame(0, $size->stock->fresh()->reserved);
    }

    public function test_sequential_decrements_within_transactions(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 5);

        DB::transaction(fn () => $this->stockService->decrementForOrder($size->id, 2));
        DB::transaction(fn () => $this->stockService->decrementForOrder($size->id, 2));

        $this->assertSame(1, Stock::query()->where('size_id', $size->id)->value('quantity'));
    }

    public function test_decrement_throws_when_insufficient(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 1);

        $this->expectException(\RuntimeException::class);

        DB::transaction(fn () => $this->stockService->decrementForOrder($size->id, 2));
    }
}
