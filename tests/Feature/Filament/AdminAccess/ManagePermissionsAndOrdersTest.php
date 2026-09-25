<?php

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ManageUserPermissions;
use Domain\AdminAccess\AdminPermission;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('allows panel access for super admin and level 3 users', function () {
    $super = User::factory()->create([
        'email' => 'admin@gmail.com',
        'level' => 0,
        'status' => 1,
    ]);
    $levelThree = User::factory()->admin()->create(['email' => 'ops@example.com']);
    $normal = User::factory()->create(['level' => 0, 'email' => 'user@example.com']);

    expect($super->canAccessPanel(filament()->getDefaultPanel()))->toBeTrue()
        ->and($levelThree->canAccessPanel(filament()->getDefaultPanel()))->toBeTrue()
        ->and($normal->canAccessPanel(filament()->getDefaultPanel()))->toBeFalse();
});

it('forbids manage permissions page for non super admins', function () {
    $target = User::factory()->create();
    $this->actingAsAdminWithPermissions([AdminPermission::USERS_VIEW, AdminPermission::USERS_MANAGE_PERMISSIONS]);

    livewire(ManageUserPermissions::class, ['record' => $target->id])
        ->assertForbidden();
});

it('lets super admin sync permissions from the manage page', function () {
    $this->actingAsSuperAdmin();
    $target = User::factory()->create(['level' => 0, 'email' => 'brand-owner@example.com']);
    $brand = Brand::factory()->create();

    livewire(ManageUserPermissions::class, ['record' => $target->id])
        ->assertSuccessful()
        ->fillForm([
            'brand_ids' => [$brand->id],
            'module_permissions' => [
                'products' => [AdminPermission::PRODUCTS_VIEW],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $target->refresh();

    expect($target->level)->toBe(3)
        ->and($target->can(AdminPermission::PRODUCTS_VIEW))->toBeTrue()
        ->and($target->adminBrands()->pluck('brands.id')->all())->toBe([$brand->id]);
});

it('shows manage permissions action only for super admin', function () {
    $this->actingAsSuperAdmin();

    livewire(ListUsers::class)
        ->assertSuccessful()
        ->assertTableActionExists('manage_permissions');

    $this->actingAsAdminWithPermissions([AdminPermission::USERS_VIEW]);

    livewire(ListUsers::class)
        ->assertSuccessful()
        ->assertTableActionHidden('manage_permissions');
});

it('blocks refund without refund permission while allowing status change', function () {
    $brand = Brand::factory()->create();
    $buyer = User::factory()->create(['status' => 1]);
    $this->createWalletFor($buyer, 0);
    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'total_amount' => 1000,
    ]);
    $order->products()->attach($product->id, [
        'count' => 1,
        'amount' => 1000,
        'status' => Order::PAID,
    ]);

    $this->actingAsAdminWithPermissions(
        [AdminPermission::ORDERS_VIEW, AdminPermission::ORDERS_CHANGE_STATUS],
        [$brand->id],
    );

    livewire(ListOrders::class)
        ->callTableAction('change_status', $order, data: [
            'status' => Order::REFUNDED,
        ]);

    expect($order->fresh()->status)->toBe(Order::PAID);

    livewire(ListOrders::class)
        ->callTableAction('change_status', $order, data: [
            'status' => Order::SHIPPED,
        ])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(Order::SHIPPED);
});

it('refunds when user has refund permission', function () {
    $brand = Brand::factory()->create();
    $buyer = User::factory()->create(['status' => 1]);
    $this->createWalletFor($buyer, 0);
    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'total_amount' => 1500,
    ]);
    $order->products()->attach($product->id, [
        'count' => 1,
        'amount' => 1500,
        'status' => Order::PAID,
    ]);

    $this->actingAsAdminWithPermissions(
        [AdminPermission::ORDERS_VIEW, AdminPermission::ORDERS_REFUND],
        [$brand->id],
    );

    livewire(ListOrders::class)
        ->callTableAction('change_status', $order, data: [
            'status' => Order::REFUNDED,
        ])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(Order::REFUNDED);
});

it('exposes the permissions page route for users resource', function () {
    expect(UserResource::getPages())->toHaveKey('permissions');
});
