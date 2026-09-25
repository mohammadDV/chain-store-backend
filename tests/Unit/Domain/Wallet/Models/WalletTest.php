<?php

use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('determines whether the balance can cover a withdrawal', function () {
    $wallet = Wallet::factory()->make(['balance' => 100_000]);

    expect($wallet->canWithdraw(100_000))->toBeTrue()
        ->and($wallet->canWithdraw(99_999))->toBeTrue()
        ->and($wallet->canWithdraw(100_001))->toBeFalse();
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();

    expect($wallet->user())->toBeInstanceOf(BelongsTo::class)
        ->and($wallet->user->is($user))->toBeTrue();
});

it('has wallet transactions', function () {
    $wallet = Wallet::factory()->create();
    $transactions = WalletTransaction::factory()->count(2)->create([
        'wallet_id' => $wallet->id,
    ]);

    expect($wallet->walletTransaction())->toBeInstanceOf(HasMany::class)
        ->and($wallet->walletTransaction)->toHaveCount(2)
        ->and($wallet->walletTransaction->pluck('id')->all())
        ->toEqualCanonicalizing($transactions->pluck('id')->all());
});
