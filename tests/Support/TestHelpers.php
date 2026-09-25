<?php

namespace Tests\Support;

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Models\Stock;
use Domain\Product\Services\StockService;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Domain\User\Services\TelegramNotificationService;
use Domain\Wallet\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;

trait TestHelpers
{
    protected function mockTelegram(): MockInterface
    {
        $telegram = Mockery::mock(TelegramNotificationService::class);
        $telegram->shouldReceive('sendNotification')->withAnyArgs()->andReturnNull();
        $this->app->instance(TelegramNotificationService::class, $telegram);

        return $telegram;
    }

    protected function fakeRecaptchaSuccess(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
        ]);
    }

    protected function actingAsUser(?User $user = null): User
    {
        $user ??= User::factory()->create([
            'status' => 1,
            'email_verified_at' => now(),
            'verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    protected function createWalletFor(User $user, float $balance = 0): Wallet
    {
        return Wallet::factory()->for($user)->withBalance($balance)->create();
    }

    protected function seedRoles(): void
    {
        if (Role::query()->where('name', 'user')->exists()) {
            return;
        }

        Role::create([
            'id' => 1,
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'id' => 2,
            'name' => 'user',
            'guard_name' => 'web',
        ]);
    }

    /**
     * @return array{0: User, 1: Product, 2: Size, 3: Stock}
     */
    protected function seedProductWithStock(int $quantity = 10, bool $hasStockManagement = true): array
    {
        $owner = User::factory()->create();
        $brand = Brand::factory()->create([
            'has_stock_management' => $hasStockManagement ? 1 : 0,
        ]);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'user_id' => $owner->id,
            'amount' => 100000,
            'active' => 1,
            'status' => Product::COMPLETED,
            'is_failed' => 0,
        ]);
        $size = Size::factory()->create([
            'product_id' => $product->id,
            'status' => 1,
        ]);

        $stock = $size->stock;
        if ($stock) {
            app(StockService::class)->setQuantity($size->id, $quantity);
            $stock->refresh();
        } else {
            $stock = Stock::factory()->create([
                'size_id' => $size->id,
                'quantity' => $quantity,
                'reserved' => 0,
            ]);
        }

        return [$owner, $product, $size, $stock];
    }
}
