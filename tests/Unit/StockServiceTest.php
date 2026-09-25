<?php

namespace Tests\Unit;

use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
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
        $this->assertDatabaseCount('inventory_transactions', 0);
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
        $this->assertSame(3, $size->stock->fresh()->reserved);
    }

    public function test_set_quantity_writes_adjust_ledger(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);

        $this->stockService->setQuantity(
            $size->id,
            15,
            InventoryTransactionSource::Admin,
            null,
            'manual adjust',
        );

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Adjust->value,
            'source' => InventoryTransactionSource::Admin,
            'quantity_change' => 15,
            'previous_quantity' => 0,
            'resulting_quantity' => 15,
            'description' => 'manual adjust',
        ]);
    }

    public function test_set_quantity_skips_ledger_when_unchanged(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        $this->assertDatabaseCount('inventory_transactions', 1);

        $this->stockService->setQuantity($size->id, 10);

        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertSame(10, $size->stock->fresh()->quantity);
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

    public function test_decrement_for_order_reduces_quantity_and_writes_sale(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        DB::beginTransaction();
        $this->stockService->decrementForOrder($size->id, 3, $user->id, 'Order ABC');
        DB::commit();

        $this->assertSame(7, $size->stock->fresh()->quantity);
        $this->assertSame(0, $size->stock->fresh()->reserved);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Sale->value,
            'source' => InventoryTransactionSource::Order,
            'user_id' => $user->id,
            'quantity_change' => -3,
            'previous_quantity' => 10,
            'resulting_quantity' => 7,
            'description' => 'Order ABC',
        ]);
    }

    public function test_sequential_decrements_within_transactions(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 5);

        DB::transaction(fn () => $this->stockService->decrementForOrder($size->id, 2));
        DB::transaction(fn () => $this->stockService->decrementForOrder($size->id, 2));

        $this->assertSame(1, Stock::query()->where('size_id', $size->id)->value('quantity'));
        $this->assertSame(
            2,
            InventoryTransaction::query()
                ->where('size_id', $size->id)
                ->where('type', InventoryTransactionType::Sale)
                ->count()
        );
    }

    public function test_decrement_throws_when_insufficient_and_rolls_back(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 1);
        $ledgerBefore = InventoryTransaction::query()->count();

        try {
            DB::transaction(fn () => $this->stockService->decrementForOrder($size->id, 2));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        $this->assertSame(1, $size->stock->fresh()->quantity);
        $this->assertSame($ledgerBefore, InventoryTransaction::query()->count());
    }

    public function test_ledger_failure_rolls_back_quantity_change(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        try {
            DB::transaction(function () use ($size) {
                $stock = Stock::query()->where('size_id', $size->id)->lockForUpdate()->firstOrFail();
                $stock->update(['quantity' => 5]);
                InventoryTransaction::query()->create([
                    'product_id' => 999999999,
                    'size_id' => $size->id,
                    'type' => InventoryTransactionType::Adjust,
                    'source' => InventoryTransactionSource::System,
                    'user_id' => null,
                    'quantity_change' => -5,
                    'previous_quantity' => 10,
                    'resulting_quantity' => 5,
                    'description' => 'fail',
                ]);
            });
            $this->fail('Expected foreign key failure.');
        } catch (\Throwable) {
            // expected — invalid product_id must roll back the stock update
        }

        $this->assertSame(10, $size->stock->fresh()->quantity);
        $this->assertDatabaseMissing('inventory_transactions', [
            'description' => 'fail',
        ]);
    }

    public function test_purchase_return_reserve_release_write_typed_ledgers(): void
    {
        $product = Product::factory()->create();
        $size = Size::factory()->create(['product_id' => $product->id]);
        $this->stockService->setQuantity($size->id, 10);

        $this->stockService->purchase($size->id, 5);
        $this->assertSame(15, $size->stock->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Purchase->value,
            'quantity_change' => 5,
            'resulting_quantity' => 15,
        ]);

        $this->stockService->reserve($size->id, 3);
        $this->assertSame(12, $size->stock->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Reserve->value,
            'quantity_change' => -3,
            'resulting_quantity' => 12,
        ]);

        $this->stockService->release($size->id, 2);
        $this->assertSame(14, $size->stock->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Release->value,
            'quantity_change' => 2,
            'resulting_quantity' => 14,
        ]);

        $this->stockService->returnStock($size->id, 1);
        $this->assertSame(15, $size->stock->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'size_id' => $size->id,
            'type' => InventoryTransactionType::Return->value,
            'quantity_change' => 1,
            'resulting_quantity' => 15,
        ]);
    }
}
