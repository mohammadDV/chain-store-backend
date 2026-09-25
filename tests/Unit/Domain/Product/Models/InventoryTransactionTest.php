<?php

use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to product, size and optional user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $size = Size::factory()->create(['product_id' => $product->id]);

    $transaction = InventoryTransaction::factory()->create([
        'product_id' => $product->id,
        'size_id' => $size->id,
        'user_id' => $user->id,
    ]);

    expect($transaction->product())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->product->is($product))->toBeTrue()
        ->and($transaction->size())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->size->is($size))->toBeTrue()
        ->and($transaction->user())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->user->is($user))->toBeTrue();
});

it('casts type to inventory transaction type enum', function () {
    $transaction = InventoryTransaction::factory()->create([
        'type' => InventoryTransactionType::Sale,
        'source' => InventoryTransactionSource::Order,
        'quantity_change' => -1,
        'previous_quantity' => 5,
        'resulting_quantity' => 4,
    ]);

    expect($transaction->type)->toBe(InventoryTransactionType::Sale)
        ->and($transaction->source)->toBe(InventoryTransactionSource::Order)
        ->and($transaction->quantity_change)->toBe(-1)
        ->and($transaction->previous_quantity)->toBe(5)
        ->and($transaction->resulting_quantity)->toBe(4);
});

it('allows null user and description', function () {
    $transaction = InventoryTransaction::factory()->create([
        'user_id' => null,
        'description' => null,
        'type' => InventoryTransactionType::Purchase,
        'source' => InventoryTransactionSource::Scraper,
        'quantity_change' => 10,
        'previous_quantity' => 0,
        'resulting_quantity' => 10,
    ]);

    expect($transaction->user)->toBeNull()
        ->and($transaction->description)->toBeNull()
        ->and($transaction->type)->toBe(InventoryTransactionType::Purchase);
});

it('stores adjust and release ledger entries', function () {
    $adjust = InventoryTransaction::factory()->create([
        'type' => InventoryTransactionType::Adjust,
        'source' => InventoryTransactionSource::Admin,
        'quantity_change' => 3,
        'previous_quantity' => 2,
        'resulting_quantity' => 5,
        'description' => 'manual restock',
    ]);
    $release = InventoryTransaction::factory()->create([
        'type' => InventoryTransactionType::Release,
        'source' => InventoryTransactionSource::System,
        'quantity_change' => 1,
        'previous_quantity' => 0,
        'resulting_quantity' => 1,
    ]);

    expect($adjust->type)->toBe(InventoryTransactionType::Adjust)
        ->and($adjust->description)->toBe('manual restock')
        ->and($release->type)->toBe(InventoryTransactionType::Release);
});
