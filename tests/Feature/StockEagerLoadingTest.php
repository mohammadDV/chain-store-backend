<?php

namespace Tests\Feature;

use Application\Api\Product\Resources\SizeResource;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockEagerLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_loading_sizes_does_not_n_plus_one_stock_for_size_resource(): void
    {
        $product = Product::factory()->create([
            'active' => 1,
            'status' => Product::COMPLETED,
            'is_failed' => 0,
        ]);

        $sizes = Size::factory()->count(5)->create(['product_id' => $product->id, 'status' => 1]);
        $stockService = app(StockService::class);
        foreach ($sizes as $index => $size) {
            $stockService->setQuantity($size->id, $index + 1);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $loadedSizes = Size::query()->where('product_id', $product->id)->get();
        $payload = SizeResource::collection($loadedSizes)->resolve(Request::create('/'));

        $queries = collect(DB::getQueryLog());
        $stockSelects = $queries->filter(function (array $query) {
            return str_contains(strtolower($query['query']), 'from `stocks`')
                || str_contains(strtolower($query['query']), 'from "stocks"');
        });

        DB::disableQueryLog();

        $this->assertCount(5, $payload);
        $this->assertSame(1, $stockSelects->count(), 'Stock should be eager-loaded in a single query, not per size.');
        $this->assertTrue($loadedSizes->every(fn (Size $size) => $size->relationLoaded('stock')));
    }

    public function test_product_with_sizes_eager_loads_stock_in_one_batch(): void
    {
        $product = Product::factory()->create();
        Size::factory()->count(4)->create(['product_id' => $product->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $loaded = Product::query()->with('sizes')->findOrFail($product->id);
        foreach ($loaded->sizes as $size) {
            $this->assertNotNull($size->stock);
        }

        $queries = collect(DB::getQueryLog());
        $stockSelects = $queries->filter(function (array $query) {
            return str_contains(strtolower($query['query']), 'from `stocks`')
                || str_contains(strtolower($query['query']), 'from "stocks"');
        });

        DB::disableQueryLog();

        $this->assertSame(1, $stockSelects->count());
    }
}
