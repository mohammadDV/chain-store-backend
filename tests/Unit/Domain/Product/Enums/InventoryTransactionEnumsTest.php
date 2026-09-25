<?php

use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;

it('defines all inventory transaction type cases', function () {
    expect(InventoryTransactionType::cases())->toHaveCount(6)
        ->and(InventoryTransactionType::Sale->value)->toBe('sale')
        ->and(InventoryTransactionType::Return->value)->toBe('return')
        ->and(InventoryTransactionType::Adjust->value)->toBe('adjust')
        ->and(InventoryTransactionType::Purchase->value)->toBe('purchase')
        ->and(InventoryTransactionType::Reserve->value)->toBe('reserve')
        ->and(InventoryTransactionType::Release->value)->toBe('release');
});

it('can be created from type string values', function (string $value, InventoryTransactionType $expected) {
    expect(InventoryTransactionType::from($value))->toBe($expected);
})->with([
    ['sale', InventoryTransactionType::Sale],
    ['return', InventoryTransactionType::Return],
    ['adjust', InventoryTransactionType::Adjust],
    ['purchase', InventoryTransactionType::Purchase],
    ['reserve', InventoryTransactionType::Reserve],
    ['release', InventoryTransactionType::Release],
]);

it('rejects unknown type values', function () {
    InventoryTransactionType::from('unknown');
})->throws(ValueError::class);

it('exposes inventory transaction source constants', function () {
    expect(InventoryTransactionSource::System)->toBe('system')
        ->and(InventoryTransactionSource::Admin)->toBe('admin')
        ->and(InventoryTransactionSource::Scraper)->toBe('scraper')
        ->and(InventoryTransactionSource::Order)->toBe('order');
});

it('cannot be instantiated', function () {
    $reflection = new ReflectionClass(InventoryTransactionSource::class);

    expect($reflection->isInstantiable())->toBeFalse()
        ->and($reflection->getConstructor()?->isPrivate())->toBeTrue();
});
