<?php

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Color;
use Domain\Product\Models\Favorite;
use Domain\Product\Models\File;
use Domain\Product\Models\Like;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Review\Models\Review;
use Domain\Setting\Models\Setting;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::getInstance();
});

it('belongs to brand, color and user', function () {
    $brand = Brand::factory()->create();
    $color = Color::factory()->create();
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'color_id' => $color->id,
        'user_id' => $user->id,
    ]);

    expect($product->brand())->toBeInstanceOf(BelongsTo::class)
        ->and($product->brand->is($brand))->toBeTrue()
        ->and($product->color())->toBeInstanceOf(BelongsTo::class)
        ->and($product->color->is($color))->toBeTrue()
        ->and($product->user())->toBeInstanceOf(BelongsTo::class)
        ->and($product->user->is($user))->toBeTrue();
});

it('has categories, orders, sizes, files, reviews and favorites relations', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $product->categories()->attach($category);

    $order = Order::factory()->create();
    $product->orders()->attach($order, [
        'count' => 1,
        'amount' => 1000,
        'status' => Order::PENDING,
        'color_id' => null,
        'size_id' => null,
    ]);

    $size = Size::factory()->create(['product_id' => $product->id]);
    $file = File::query()->create([
        'product_id' => $product->id,
        'path' => 'products/test.jpg',
        'type' => 'image',
        'status' => 1,
        'priority' => 0,
    ]);
    $review = Review::query()->create([
        'product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
        'comment' => 'Great',
        'rate' => 5,
        'status' => Review::APPROVED,
    ]);
    $favorite = Favorite::query()->create([
        'product_id' => $product->id,
        'user_id' => User::factory()->create()->id,
    ]);

    expect($product->categories())->toBeInstanceOf(BelongsToMany::class)
        ->and($product->categories)->toHaveCount(1)
        ->and($product->orders())->toBeInstanceOf(BelongsToMany::class)
        ->and($product->orders)->toHaveCount(1)
        ->and($product->sizes())->toBeInstanceOf(HasMany::class)
        ->and($product->sizes->first()->is($size))->toBeTrue()
        ->and($product->files())->toBeInstanceOf(HasMany::class)
        ->and($product->files->first()->is($file))->toBeTrue()
        ->and($product->reviews())->toBeInstanceOf(HasMany::class)
        ->and($product->reviews->first()->is($review))->toBeTrue()
        ->and($product->favorites())->toBeInstanceOf(HasMany::class)
        ->and($product->favorites->first()->is($favorite))->toBeTrue();
});

it('has morph many likes', function () {
    $product = Product::factory()->create();
    $like = Like::query()->create([
        'likeable_id' => $product->id,
        'likeable_type' => Product::class,
        'user_id' => User::factory()->create()->id,
        'is_like' => 1,
    ]);

    expect($product->likes())->toBeInstanceOf(MorphMany::class)
        ->and($product->likes->first()->is($like))->toBeTrue();
});

it('casts vip, active, priority and related_products', function () {
    $product = Product::factory()->create([
        'vip' => 1,
        'active' => 1,
        'priority' => 3,
        'related_products' => [1, 2, 3],
    ]);

    expect($product->vip)->toBeTrue()
        ->and($product->active)->toBeTrue()
        ->and($product->priority)->toBe(3)
        ->and($product->related_products)->toBe([1, 2, 3]);
});

it('computes payable amount from exchange and profit rates', function () {
    Setting::query()->where('id', 1)->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
    ]);

    $product = Product::factory()->create(['amount' => 100, 'discount' => 0]);

    // 100 * 3000 = 300000; profit 40% = 120000; ceil((420000)/1000)*1000 = 420000
    expect($product->amount)->toBe(420000.0);
});

it('rounds payable amount up to the nearest thousand', function () {
    Setting::query()->where('id', 1)->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
    ]);

    $product = Product::factory()->create(['amount' => 101, 'discount' => 0]);

    // 101 * 3000 = 303000; profit = 121200; total = 424200 -> 425000
    expect($product->amount)->toBe(425000.0);
});

it('scopes active products with in-stock sizes', function () {
    [, $activeProduct] = $this->seedProductWithStock(5);
    $inactive = Product::factory()->create(['active' => 0, 'status' => Product::COMPLETED]);
    $pending = Product::factory()->create(['active' => 1, 'status' => Product::PENDING]);
    $failed = Product::factory()->create(['active' => 1, 'status' => Product::COMPLETED, 'is_failed' => 1]);
    $noStock = Product::factory()->create(['active' => 1, 'status' => Product::COMPLETED]);
    Size::factory()->create(['product_id' => $noStock->id, 'status' => 1]);

    $ids = Product::query()->active()->pluck('id')->all();

    expect($ids)->toContain($activeProduct->id)
        ->and($ids)->not->toContain($inactive->id)
        ->and($ids)->not->toContain($pending->id)
        ->and($ids)->not->toContain($failed->id)
        ->and($ids)->not->toContain($noStock->id);
});

it('scopes stale products for refresh', function () {
    Carbon::setTestNow('2026-09-25 12:00:00');

    $stale = Product::factory()->create([
        'active' => 1,
        'status' => Product::COMPLETED,
        'code' => 'STALE123',
        'updated_at' => now()->subHours(50),
    ]);
    $fresh = Product::factory()->create([
        'active' => 1,
        'status' => Product::COMPLETED,
        'code' => 'FRESH123',
        'updated_at' => now()->subHours(1),
    ]);
    $noCode = Product::factory()->create([
        'active' => 1,
        'status' => Product::COMPLETED,
        'code' => '',
        'updated_at' => now()->subHours(50),
    ]);

    $ids = Product::query()->staleForRefresh(now()->subHours(48))->pluck('id')->all();

    expect($ids)->toContain($stale->id)
        ->and($ids)->not->toContain($fresh->id)
        ->and($ids)->not->toContain($noCode->id);

    Carbon::setTestNow();
});
