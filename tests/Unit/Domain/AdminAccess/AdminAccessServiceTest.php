<?php

use Domain\AdminAccess\AdminPermission;
use Domain\AdminAccess\Services\AdminAccessService;
use Domain\Brand\Models\Brand;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('identifies configured emails as super admin', function () {
    $service = app(AdminAccessService::class);
    $super = User::factory()->create(['email' => 'admin@gmail.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);

    expect($service->isSuperAdmin($super))->toBeTrue()
        ->and($service->isSuperAdmin($other))->toBeFalse();
});

it('allows super admin all gates via Gate::before', function () {
    $super = User::factory()->create(['email' => 'admin@gmail.com', 'level' => 3]);

    expect(Gate::forUser($super)->allows(AdminPermission::PRODUCTS_CREATE))->toBeTrue()
        ->and(Gate::forUser($super)->allows(AdminPermission::ORDERS_REFUND))->toBeTrue();
});

it('denies permission gates for regular users without grants', function () {
    $user = User::factory()->admin()->create(['email' => 'ops@example.com']);

    expect(Gate::forUser($user)->allows(AdminPermission::PRODUCTS_CREATE))->toBeFalse();
});

it('syncs permissions brands and level', function () {
    $service = app(AdminAccessService::class);
    $user = User::factory()->create(['level' => 0, 'email' => 'scoped@example.com']);
    $brand = Brand::factory()->create();

    $service->syncForUser($user, [AdminPermission::PRODUCTS_VIEW], [$brand->id]);
    $user->refresh();

    expect($user->level)->toBe(3)
        ->and($user->can(AdminPermission::PRODUCTS_VIEW))->toBeTrue()
        ->and($user->adminBrands()->pluck('brands.id')->all())->toBe([$brand->id]);

    $service->syncForUser($user, [], []);
    $user->refresh();

    expect($user->level)->toBe(0)
        ->and($user->getPermissionNames())->toBeEmpty()
        ->and($user->adminBrands)->toBeEmpty();
});

it('returns null brand ids for super admin and empty list when none assigned', function () {
    $service = app(AdminAccessService::class);
    $super = User::factory()->create(['email' => 'admin@gmail.com']);
    $scoped = User::factory()->create(['email' => 'brand-owner@example.com']);

    expect($service->allowedBrandIds($super))->toBeNull()
        ->and($service->allowedBrandIds($scoped))->toBe([]);
});
