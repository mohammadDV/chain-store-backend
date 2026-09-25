<?php

use App\Filament\Resources\WithdrawalTransactionResource\Pages\ListWithdrawalTransactions;
use Domain\User\Models\User;
use Domain\Wallet\Models\WithdrawalTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('lists withdrawal transactions', function () {
    livewire(ListWithdrawalTransactions::class)->assertSuccessful();
});

it('completes a pending withdrawal from the list table', function () {
    $user = User::factory()->create(['status' => 1]);
    $wallet = $this->createWalletFor($user, 5000);
    $withdrawal = WithdrawalTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'amount' => 1000,
        'status' => WithdrawalTransaction::PENDING,
    ]);

    livewire(ListWithdrawalTransactions::class)
        ->callTableAction('complete', $withdrawal, data: [
            'reason' => 'paid via bank',
        ])
        ->assertHasNoTableActionErrors();

    expect($withdrawal->fresh()->status)->toBe(WithdrawalTransaction::COMPLETED);
});

it('rejects a pending withdrawal and refunds the wallet', function () {
    $user = User::factory()->create(['status' => 1]);
    $wallet = $this->createWalletFor($user, 5000);
    $withdrawal = WithdrawalTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'amount' => 1000,
        'status' => WithdrawalTransaction::PENDING,
    ]);

    $balanceBefore = (float) $wallet->balance;

    livewire(ListWithdrawalTransactions::class)
        ->callTableAction('reject', $withdrawal, data: [
            'reason' => 'invalid card',
        ])
        ->assertHasNoTableActionErrors();

    $withdrawal->refresh();
    $wallet->refresh();

    expect($withdrawal->status)->toBe(WithdrawalTransaction::REJECT)
        ->and((float) $wallet->balance)->toBeGreaterThanOrEqual($balanceBefore);
});
