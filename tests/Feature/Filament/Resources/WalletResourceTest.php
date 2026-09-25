<?php

use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Filament\Resources\WalletResource\Pages\ViewWallet;
use Domain\User\Models\User;
use Domain\Wallet\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('lists wallets', function () {
    livewire(ListWallets::class)->assertSuccessful();
});

it('adjusts wallet balance upward from the view page', function () {
    $user = User::factory()->create(['status' => 1]);
    $wallet = $this->createWalletFor($user, 1000);

    livewire(ViewWallet::class, ['record' => $wallet->getRouteKey()])
        ->callAction('adjust_balance', data: [
            'adjustment_type' => 'increase',
            'amount' => 250,
            'description' => 'admin credit',
            'send_notification' => false,
        ])
        ->assertHasNoActionErrors();

    $wallet->refresh();

    expect((float) $wallet->balance)->toBe(1250.0)
        ->and(WalletTransaction::query()->where('wallet_id', $wallet->id)->where('type', 'deposit')->exists())->toBeTrue();
});

it('adjusts wallet balance downward from the view page', function () {
    $user = User::factory()->create(['status' => 1]);
    $wallet = $this->createWalletFor($user, 1000);

    livewire(ViewWallet::class, ['record' => $wallet->getRouteKey()])
        ->callAction('adjust_balance', data: [
            'adjustment_type' => 'decrease',
            'amount' => 200,
            'description' => 'admin debit',
            'send_notification' => false,
        ])
        ->assertHasNoActionErrors();

    $wallet->refresh();

    expect((float) $wallet->balance)->toBe(800.0);
});
