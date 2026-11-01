<?php

use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\InvoiceUnlockService;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createPaidOrderForUnlock(): Order
{
    $buyer = User::factory()->create([
        'mobile' => '09121234567',
    ]);

    $product = Product::factory()->create([
        'title' => 'کفش تست',
        'code' => 'PRD-100',
        'image' => 'products/shoe.jpg',
    ]);

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'active' => 1,
        'amount' => 200000,
        'discount_amount' => 10000,
        'delivery_amount' => 30000,
        'total_amount' => 220000,
        'product_count' => 2,
        'description' => 'تحویل عصر',
        'vip' => 1,
        'fullname' => 'علی رضایی',
        'address' => 'تهران، خیابان ولیعصر',
        'postal_code' => '1234567890',
    ]);

    $order->products()->attach($product->id, [
        'count' => 2,
        'amount' => 100000,
        'status' => Order::PAID,
        'color_id' => null,
        'size_id' => null,
    ]);

    return $order->fresh();
}

it('rejects recipient unlock when password is not configured', function () {
    $order = createPaidOrderForUnlock();

    $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [
        'password' => 'anything',
    ])
        ->assertStatus(503)
        ->assertJsonPath('status', 0)
        ->assertJsonPath('code', 'invoice_unlock_password_not_configured');
});

it('hides order details until unlocked and returns the full order after password', function () {
    Setting::getInstance()->update([
        'invoice_unlock_password' => Hash::make('courier-secret'),
    ]);

    $order = createPaidOrderForUnlock();

    $this->getJson('/api/orders/by-code/'.$order->code)
        ->assertOk()
        ->assertJsonPath('data.code', $order->code)
        ->assertJsonPath('data.unlock_available', true)
        ->assertJsonMissingPath('data.fullname')
        ->assertJsonMissingPath('data.address')
        ->assertJsonMissingPath('data.amount')
        ->assertJsonMissingPath('data.products')
        ->assertJsonMissingPath('data.status');

    $unlock = $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [
        'password' => 'courier-secret',
    ])
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.code', $order->code)
        ->assertJsonPath('data.status', Order::PAID)
        ->assertJsonPath('data.fullname', 'علی رضایی')
        ->assertJsonPath('data.mobile', '09121234567')
        ->assertJsonPath('data.address', 'تهران، خیابان ولیعصر')
        ->assertJsonPath('data.postal_code', '1234567890')
        ->assertJsonPath('data.description', 'تحویل عصر')
        ->assertJsonPath('data.vip', true)
        ->assertJsonPath('data.amount', 200000)
        ->assertJsonPath('data.total_amount', 220000)
        ->assertJsonPath('data.products.0.title', 'کفش تست')
        ->assertJsonPath('data.products.0.count', 2)
        ->json('data');

    expect($unlock['unlock_token'])->toBeString()->not->toBeEmpty()
        ->and($unlock['unlock_expires_at'])->toBeInt();

    $reuse = $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [
        'unlock_token' => $unlock['unlock_token'],
    ])
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.fullname', 'علی رضایی')
        ->assertJsonPath('data.products.0.title', 'کفش تست')
        ->assertJsonPath('data.unlock_token', $unlock['unlock_token'])
        ->json('data');

    expect($reuse['unlock_expires_at'])->toBeInt()
        ->and($reuse['unlock_expires_at'])->toBe($unlock['unlock_expires_at']);
});

it('rejects missing credentials and expired unlock tokens', function () {
    Setting::getInstance()->update([
        'invoice_unlock_password' => Hash::make('courier-secret'),
    ]);

    $order = createPaidOrderForUnlock();

    $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [])
        ->assertStatus(422);

    $service = app(InvoiceUnlockService::class);
    $issued = $service->issueToken();

    $this->travel(25)->hours();

    $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [
        'unlock_token' => $issued['token'],
    ])
        ->assertUnauthorized()
        ->assertJsonPath('status', 0)
        ->assertJsonPath('code', 'invoice_unlock_token_invalid');
});

it('rejects wrong password and invalid token', function () {
    Setting::getInstance()->update([
        'invoice_unlock_password' => Hash::make('courier-secret'),
    ]);

    $order = createPaidOrderForUnlock();

    $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('status', 0)
        ->assertJsonPath('code', 'invoice_unlock_password_invalid');

    $this->postJson('/api/orders/by-code/'.$order->code.'/recipient', [
        'unlock_token' => 'not-a-valid-token',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('status', 0)
        ->assertJsonPath('code', 'invoice_unlock_token_invalid');
});

it('issues and verifies unlock tokens via InvoiceUnlockService', function () {
    $service = app(InvoiceUnlockService::class);

    Setting::getInstance()->update([
        'invoice_unlock_password' => Hash::make('rotate-me'),
    ]);

    expect($service->hasPassword())->toBeTrue()
        ->and($service->verifyPassword('rotate-me'))->toBeTrue()
        ->and($service->verifyPassword('nope'))->toBeFalse();

    $issued = $service->issueToken();

    expect($service->verifyToken($issued['token']))->toBeTrue()
        ->and($service->verifyToken('broken'))->toBeFalse();
});
