<?php

use App\Filament\Resources\ManualOrderResource\Pages\CreateManualOrder;
use App\Filament\Resources\ManualOrderResource\Pages\ListManualOrders;
use Domain\Product\Models\ManualOrder;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\SettingService;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
    app(SettingService::class)->clearCache();
});

it('lists manual orders', function () {
    livewire(ListManualOrders::class)->assertSuccessful();
});

it('creates a manual order from raw foreign price like OrderRepository', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
        'delivery_amount' => 400_000,
        'limit_delivery_amount' => 12_000_000,
    ]);
    app(SettingService::class)->clearCache();

    livewire(CreateManualOrder::class)
        ->fillForm([
            'calc_raw_price' => 1000,
            'product_name' => 'Quoted Shoe',
            'amount' => 4_200_000,
            'fullname' => 'Ali Rezaei',
            'mobile' => '09121234567',
            'active' => true,
            'vip' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = ManualOrder::query()->where('product_name', 'Quoted Shoe')->first();

    expect($order)->not->toBeNull()
        ->and((float) $order->amount)->toBe(4200000.0)
        ->and((float) $order->delivery_amount)->toBe(400000.0)
        ->and((float) $order->total_amount)->toBe(4600000.0)
        ->and((float) $order->profit)->toBe((4200000.0 * 40 / 100) + 400000.0)
        ->and((float) $order->profit_rate)->toBe(40.0)
        ->and((float) $order->exchange_rate)->toBe(3000.0);
});

it('creates a manual order from the admin panel', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
        'delivery_amount' => 180_000,
        'limit_delivery_amount' => 2_000_000,
    ]);
    app(SettingService::class)->clearCache();

    livewire(CreateManualOrder::class)
        ->fillForm([
            'product_name' => 'Manual Shoe',
            'brand' => 'Nike',
            'amount' => 420_000,
            'description' => 'https://example.com/product',
            'fullname' => 'Ali Rezaei',
            'mobile' => '09121234567',
            'email' => 'ali@example.com',
            'address' => 'Tehran',
            'postal_code' => '1234567890',
            'user_id' => null,
            'active' => true,
            'vip' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = ManualOrder::query()->where('product_name', 'Manual Shoe')->first();

    expect($order)->not->toBeNull()
        ->and($order->brand)->toBe('Nike')
        ->and($order->status)->toBe(ManualOrder::PENDING)
        ->and($order->code)->toMatch('/^\d{16}$/')
        ->and($order->user_id)->toBeNull()
        ->and((float) $order->amount)->toBe(420000.0)
        ->and((float) $order->delivery_amount)->toBe(180000.0)
        ->and((float) $order->total_amount)->toBe(600000.0)
        ->and((float) $order->profit_rate)->toBe(40.0)
        ->and((float) $order->exchange_rate)->toBe(3000.0)
        ->and($order->fullname)->toBe('Ali Rezaei')
        ->and($order->mobile)->toBe('09121234567');
});

it('creates a manual order linked to an existing user', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 1,
        'profit_rate' => 0,
        'delivery_amount' => 0,
        'limit_delivery_amount' => 0,
    ]);
    app(SettingService::class)->clearCache();

    $buyer = User::factory()->create([
        'nickname' => 'buyer_nick',
        'status' => 1,
    ]);

    livewire(CreateManualOrder::class)
        ->fillForm([
            'product_name' => 'Linked Product',
            'amount' => 100_000,
            'fullname' => 'Buyer Name',
            'mobile' => '09120000001',
            'user_id' => $buyer->id,
            'active' => true,
            'vip' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = ManualOrder::query()->where('product_name', 'Linked Product')->first();

    expect($order)->not->toBeNull()
        ->and($order->user_id)->toBe($buyer->id)
        ->and((float) $order->delivery_amount)->toBe(0.0)
        ->and((float) $order->total_amount)->toBe(100000.0);
});

it('changes a manual order status from the list table', function () {
    $order = ManualOrder::query()->create([
        'user_id' => null,
        'code' => ManualOrder::generateCode(),
        'status' => ManualOrder::PENDING,
        'product_count' => 1,
        'amount' => 100_000,
        'delivery_amount' => 0,
        'total_amount' => 100_000,
        'profit' => 0,
        'profit_rate' => 40,
        'exchange_rate' => 3000,
        'active' => 1,
        'vip' => 0,
        'product_name' => 'Status Product',
        'fullname' => 'Customer',
        'mobile' => '09123334455',
    ]);

    livewire(ListManualOrders::class)
        ->callTableAction('change_status', $order, data: [
            'status' => ManualOrder::PAID,
        ])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(ManualOrder::PAID);
});

it('requires product name and amount when creating', function () {
    livewire(CreateManualOrder::class)
        ->fillForm([
            'product_name' => '',
            'amount' => null,
            'fullname' => '',
            'mobile' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'product_name' => 'required',
            'amount' => 'required',
            'fullname' => 'required',
            'mobile' => 'required',
        ]);
});
