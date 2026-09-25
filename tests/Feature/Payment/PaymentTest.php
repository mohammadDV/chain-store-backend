<?php

use Domain\Payment\Models\Transaction;
use Domain\User\Models\User;
use Domain\Wallet\Models\WalletTransaction;
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('rejects an invalid payment signature', function () {
    $transaction = Transaction::factory()->create();

    $this->getJson("/api/payment?transaction={$transaction->id}&sign=invalid")
        ->assertOk()
        ->assertJson([
            'status' => 0,
            'message' => Lang::get('site.invalid_request'),
        ]);
});

it('redirects to the gateway for a valid signed payment request', function () {
    $transaction = Transaction::factory()->create();

    Toman::fakeRequest()
        ->withTransactionId('gateway-request-1')
        ->successful();

    $response = $this->get('/api/payment?'.http_build_query([
        'transaction' => $transaction->id,
        'sign' => Transaction::generateHash((string) $transaction->id),
    ]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('gateway-request-1')
        ->and($transaction->fresh()->bank_transaction_id)->toBe('gateway-request-1');
});

it('credits a wallet top up once after a successful callback', function () {
    $user = User::factory()->create();
    $wallet = $this->createWalletFor($user);
    $walletTransaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'amount' => 50_000,
        'status' => WalletTransaction::PENDING,
    ]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'model_id' => $walletTransaction->id,
        'model_type' => Transaction::WALLET,
        'amount' => 50_000,
        'bank_transaction_id' => 'gateway-callback-1',
        'status' => Transaction::PENDING,
    ]);

    Toman::fakeVerification()
        ->withTransactionId('gateway-callback-1')
        ->withReferenceId('reference-1')
        ->successful();

    $this->get('/api/payment/callback')->assertRedirect('/payment/result/gateway-callback-1');

    expect((float) $wallet->fresh()->balance)->toBe(50_000.0)
        ->and($walletTransaction->fresh()->status)->toBe(WalletTransaction::COMPLETED)
        ->and($transaction->fresh()->status)->toBe(Transaction::COMPLETED)
        ->and($transaction->fresh()->reference)->toBe('reference-1');
});

it('does not double credit a completed transaction callback replay', function () {
    $user = User::factory()->create();
    $wallet = $this->createWalletFor($user, 50_000);
    $walletTransaction = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'amount' => 50_000,
        'status' => WalletTransaction::COMPLETED,
    ]);
    Transaction::factory()->completed()->create([
        'user_id' => $user->id,
        'model_id' => $walletTransaction->id,
        'model_type' => Transaction::WALLET,
        'amount' => 50_000,
        'bank_transaction_id' => 'gateway-replay-1',
    ]);

    Toman::fakeVerification()
        ->withTransactionId('gateway-replay-1')
        ->withReferenceId('reference-replay')
        ->successful();

    $this->get('/api/payment/callback')->assertRedirect('/payment/result/gateway-replay-1');

    expect((float) $wallet->fresh()->balance)->toBe(50_000.0);
});

it('marks a transaction failed after a failed callback', function () {
    $transaction = Transaction::factory()->create([
        'bank_transaction_id' => 'gateway-failed-1',
        'status' => Transaction::PENDING,
    ]);

    Toman::fakeVerification()
        ->withTransactionId('gateway-failed-1')
        ->failed('Payment failed', Status::FAILED_TRANSACTION);

    $this->get('/api/payment/callback')->assertRedirect('/payment/result/gateway-failed-1');

    expect($transaction->fresh()->status)->toBe(Transaction::FAILED)
        ->and($transaction->fresh()->description)->toBe('Payment failed');
});

it('allows wallet manual payments only for authenticated users', function () {
    $payload = [
        'amount' => 25_000,
        'type' => Transaction::WALLET,
        'image' => 'receipts/payment.jpg',
    ];

    $this->postJson('/api/profile/payment/manual-payment', $payload)->assertUnauthorized();

    $user = $this->actingAsUser();

    $this->postJson('/api/profile/payment/manual-payment', $payload)
        ->assertOk()
        ->assertJsonPath('status', 1);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'model_type' => Transaction::WALLET,
        'amount' => 25_000,
        'manual' => 1,
    ]);
});

it('shows a public payment result by bank transaction id', function () {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 91_000,
        'bank_transaction_id' => 'public-result-1',
    ]);

    $this->getJson('/api/payment/result/public-result-1')
        ->assertOk()
        ->assertJsonPath('amount', $transaction->amount)
        ->assertJsonPath('status', Transaction::COMPLETED);
});

it('does not leak another users transactions while searching', function () {
    $user = $this->actingAsUser();
    $own = Transaction::factory()->create([
        'user_id' => $user->id,
        'reference' => 'shared-search-token-own',
    ]);
    Transaction::factory()->create([
        'user_id' => User::factory(),
        'reference' => 'shared-search-token-other',
    ]);

    $this->getJson('/api/profile/payment/transactions?query=shared-search-token')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id);
});
