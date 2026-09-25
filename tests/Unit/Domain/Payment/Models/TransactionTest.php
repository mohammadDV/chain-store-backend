<?php

use Domain\Payment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a deterministic transaction hash', function () {
    expect(Transaction::generateHash('123'))
        ->toBe(md5('sys#65687123$#$rstg@3'))
        ->and(Transaction::generateHash('123'))
        ->not->toBe(Transaction::generateHash('124'));
});

it('calculates revenue for supported transaction types', function () {
    $wallet = Transaction::factory()->make([
        'model_type' => Transaction::WALLET,
        'amount' => 125_000,
    ]);
    $order = Transaction::factory()->make([
        'model_type' => Transaction::ORDER,
        'amount' => 75_000,
    ]);
    $other = Transaction::factory()->make([
        'model_type' => Transaction::IDENTITY,
        'amount' => 50_000,
    ]);

    expect($wallet->revenue)->toBe(125_000.0)
        ->and($order->revenue)->toBe(75_000.0)
        ->and($other->revenue)->toBe(0.0);
});

it('calculates monthly total and type revenue from completed transactions', function () {
    Transaction::factory()->completed()->create([
        'model_type' => Transaction::WALLET,
        'amount' => 100_000,
        'created_at' => '2026-09-05 12:00:00',
    ]);
    Transaction::factory()->completed()->create([
        'model_type' => Transaction::ORDER,
        'amount' => 250_000,
        'created_at' => '2026-09-15 12:00:00',
    ]);
    Transaction::factory()->create([
        'model_type' => Transaction::ORDER,
        'amount' => 999_000,
        'status' => Transaction::PENDING,
        'created_at' => '2026-09-20 12:00:00',
    ]);
    Transaction::factory()->completed()->create([
        'model_type' => Transaction::ORDER,
        'amount' => 50_000,
        'created_at' => '2026-08-20 12:00:00',
    ]);

    expect(Transaction::getMonthlyRevenue(2026, 9))->toBe(350_000.0)
        ->and(Transaction::getTotalRevenue())->toBe(400_000.0)
        ->and(Transaction::getRevenueByType())->toBe([
            Transaction::WALLET => 100_000.0,
            Transaction::ORDER => 300_000.0,
        ])
        ->and(Transaction::getRevenueByMonth(2026)[9])->toBe(350_000.0);
});
