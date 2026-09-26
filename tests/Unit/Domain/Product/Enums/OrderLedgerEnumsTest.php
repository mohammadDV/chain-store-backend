<?php

use Domain\Product\Enums\OrderLedgerSource;
use Domain\Product\Enums\OrderLedgerType;

it('defines all order ledger type cases', function () {
    expect(OrderLedgerType::cases())->toHaveCount(6)
        ->and(OrderLedgerType::Created->value)->toBe('created')
        ->and(OrderLedgerType::Paid->value)->toBe('paid')
        ->and(OrderLedgerType::Expired->value)->toBe('expired')
        ->and(OrderLedgerType::StatusChanged->value)->toBe('status_changed')
        ->and(OrderLedgerType::Refunded->value)->toBe('refunded')
        ->and(OrderLedgerType::LineRefunded->value)->toBe('line_refunded');
});

it('exposes order ledger source constants', function () {
    expect(OrderLedgerSource::System)->toBe('system')
        ->and(OrderLedgerSource::Admin)->toBe('admin')
        ->and(OrderLedgerSource::Customer)->toBe('customer');
});
