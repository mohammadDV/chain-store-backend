<?php

use Domain\Payment\Models\Transaction;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Models\WithdrawalTransaction;
use Domain\Wallet\Repositories\WalletRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('returns own wallet for authenticated user', function () {
    $user = $this->actingAsUser();
    $wallet = $this->createWalletFor($user, 50000);

    $this->getJson('/api/profile/wallet')
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.currency', Wallet::IRR);

    expect((float) $this->getJson('/api/profile/wallet')->json('data.balance'))->toBe(50000.0);
});

it('rejects wallet access for guests', function () {
    $this->getJson('/api/profile/wallet')->assertUnauthorized();
});

it('creates pending top-up and payment url when verified', function () {
    $user = $this->actingAsUser();
    $this->createWalletFor($user, 0);

    $response = $this->postJson('/api/profile/wallet/top-up', [
        'amount' => 100000,
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonStructure(['url']);

    expect(WalletTransaction::where('type', 'deposit')->where('status', WalletTransaction::PENDING)->count())->toBe(1);
    expect(Transaction::where('model_type', 'wallet')->where('status', 'pending')->count())->toBe(1);
});

it('rejects top-up when account not verified', function () {
    $user = User::factory()->create([
        'status' => 1,
        'verified_at' => null,
        'email_verified_at' => now(),
    ]);
    $this->actingAsUser($user);
    $this->createWalletFor($user, 0);

    $this->postJson('/api/profile/wallet/top-up', ['amount' => 100000])
        ->assertStatus(400)
        ->assertJsonPath('status', 0);
});

it('transfers balance between wallets atomically', function () {
    $sender = $this->actingAsUser();
    $this->createWalletFor($sender, 100000);

    $recipient = User::factory()->create([
        'customer_number' => '999888777',
        'status' => 1,
        'verified_at' => now(),
    ]);
    $this->createWalletFor($recipient, 0);

    $this->postJson('/api/profile/wallet/transfer', [
        'amount' => 25000,
        'customer_number' => '999888777',
        'description' => 'test transfer',
    ])->assertOk()->assertJsonPath('status', 1);

    expect((float) Wallet::where('user_id', $sender->id)->value('balance'))->toBe(75000.0);
    expect((float) Wallet::where('user_id', $recipient->id)->value('balance'))->toBe(25000.0);
    expect(WalletTransaction::where('type', WalletTransaction::TRANSFER)->count())->toBe(2);
});

it('rejects transfer with insufficient funds', function () {
    $sender = $this->actingAsUser();
    $this->createWalletFor($sender, 5000);

    $recipient = User::factory()->create(['customer_number' => '111222333']);
    $this->createWalletFor($recipient, 0);

    $this->postJson('/api/profile/wallet/transfer', [
        'amount' => 10000,
        'customer_number' => '111222333',
    ])->assertStatus(422)->assertJsonPath('status', 0);
});

it('rejects transfer below minimum amount', function () {
    $sender = $this->actingAsUser();
    $this->createWalletFor($sender, 100000);
    $recipient = User::factory()->create(['customer_number' => '444555666']);
    $this->createWalletFor($recipient, 0);

    $this->postJson('/api/profile/wallet/transfer', [
        'amount' => 1000,
        'customer_number' => '444555666',
    ])->assertStatus(422);
});

it('rejects transfer to self', function () {
    $user = $this->actingAsUser();
    $this->createWalletFor($user, 100000);

    $this->postJson('/api/profile/wallet/transfer', [
        'amount' => 10000,
        'customer_number' => $user->customer_number,
    ])->assertNotFound();
});

it('withdraws funds and creates pending withdrawal record', function () {
    $user = $this->actingAsUser();
    $this->createWalletFor($user, 200000);

    $this->postJson('/api/profile/withdraws', [
        'amount' => 50000,
        'description' => 'cash out',
        'card' => '6037991234567890',
        'sheba' => 'IR120170000000123456789001',
    ])->assertOk()->assertJsonPath('status', 1);

    expect((float) Wallet::where('user_id', $user->id)->value('balance'))->toBe(150000.0);
    expect(WithdrawalTransaction::where('status', WithdrawalTransaction::PENDING)->count())->toBe(1);
});

it('rejects withdraw with insufficient funds', function () {
    $user = $this->actingAsUser();
    $this->createWalletFor($user, 1000);

    $this->postJson('/api/profile/withdraws', [
        'amount' => 10000,
        'description' => 'cash out',
    ])->assertStatus(422)->assertJsonPath('status', 0);
});

it('completeTopUp is idempotent and does not double credit', function () {
    $user = User::factory()->create();
    $wallet = $this->createWalletFor($user, 0);

    $wt = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'amount' => 50000,
        'type' => WalletTransaction::DEPOSITE,
        'status' => WalletTransaction::PENDING,
    ]);

    $repo = app(WalletRepository::class);
    $repo->completeTopUp($wt->id);
    $repo->completeTopUp($wt->id);

    expect((float) $wallet->fresh()->balance)->toBe(50000.0);
    expect($wt->fresh()->status)->toBe(WalletTransaction::COMPLETED);
});
