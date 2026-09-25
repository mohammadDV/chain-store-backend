<?php

use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to wallet', function () {
    $wallet = Wallet::factory()->create();
    $transaction = WalletTransaction::factory()->create(['wallet_id' => $wallet->id]);

    expect($transaction->wallet())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->wallet->is($wallet))->toBeTrue();
});

it('creates a deposit transaction and increments balance', function () {
    $wallet = Wallet::factory()->create(['balance' => 100_000]);

    $transaction = WalletTransaction::createTransaction(
        $wallet,
        25_000,
        WalletTransaction::DEPOSITE,
        'wallet top-up',
    );

    expect($transaction->type)->toBe(WalletTransaction::DEPOSITE)
        ->and((float) $transaction->amount)->toBe(25_000.0)
        ->and($transaction->status)->toBe(WalletTransaction::COMPLETED)
        ->and($transaction->reference)->toMatch('/^\d{10}$/')
        ->and((float) $wallet->fresh()->balance)->toBe(125_000.0);
});

it('creates a withdrawal transaction and decrements balance', function () {
    $wallet = Wallet::factory()->create(['balance' => 80_000]);

    WalletTransaction::createTransaction(
        $wallet,
        -30_000,
        WalletTransaction::WITHDRAWAL,
        'cash out',
    );

    expect((float) $wallet->fresh()->balance)->toBe(50_000.0);
});

it('generates unique references', function () {
    $first = WalletTransaction::generateReference();
    $second = WalletTransaction::generateReference();

    expect($first)->toMatch('/^\d{10}$/')
        ->and($second)->not->toBe($first);
});

it('exposes type and status constants', function () {
    expect(WalletTransaction::PENDING)->toBe('pending')
        ->and(WalletTransaction::COMPLETED)->toBe('completed')
        ->and(WalletTransaction::FAILED)->toBe('failed')
        ->and(WalletTransaction::DEPOSITE)->toBe('deposit')
        ->and(WalletTransaction::WITHDRAWAL)->toBe('withdrawal')
        ->and(WalletTransaction::REFUND)->toBe('refund')
        ->and(WalletTransaction::TRANSFER)->toBe('transfer')
        ->and(WalletTransaction::PURCHASE)->toBe('purchase');
});

it('defines a user relation even without a user_id column usage', function () {
    $transaction = WalletTransaction::factory()->make();

    expect($transaction->user())->toBeInstanceOf(BelongsTo::class)
        ->and($transaction->user()->getRelated())->toBeInstanceOf(User::class);
});
