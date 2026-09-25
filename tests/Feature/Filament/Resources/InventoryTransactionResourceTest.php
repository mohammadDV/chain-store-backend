<?php

use App\Filament\Resources\InventoryTransactionResource\Pages\CreateInventoryTransaction;
use App\Filament\Resources\InventoryTransactionResource\Pages\ListInventoryTransactions;
use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('lists inventory transactions', function () {
    livewire(ListInventoryTransactions::class)
        ->assertSuccessful();
});

it('mounts the inventory create page', function () {
    livewire(CreateInventoryTransaction::class)
        ->assertSuccessful();
});

it('applies inventory increases through StockService used by the admin create page', function () {
    $admin = $this->actingAsAdmin();
    [, , $size, $stock] = $this->seedProductWithStock(10);
    $previous = $stock->quantity;

    $transaction = app(StockService::class)->applyManualChange(
        $size->id,
        5,
        InventoryTransactionType::Adjust,
        InventoryTransactionSource::Admin,
        $admin->id,
        'admin restock',
    );

    $stock->refresh();

    expect($transaction)->toBeInstanceOf(InventoryTransaction::class)
        ->and($stock->quantity)->toBe($previous + 5)
        ->and(InventoryTransaction::query()->where('size_id', $size->id)->exists())->toBeTrue();
});

it('applies inventory decreases through StockService used by the admin create page', function () {
    $admin = $this->actingAsAdmin();
    [, , $size, $stock] = $this->seedProductWithStock(10);

    app(StockService::class)->applyManualChange(
        $size->id,
        -3,
        InventoryTransactionType::Adjust,
        InventoryTransactionSource::Admin,
        $admin->id,
        'admin decrease',
    );

    $stock->refresh();

    expect($stock->quantity)->toBe(7);
});
