<?php

use Domain\Brand\Models\Brand;
use Domain\Payment\Models\Transaction;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\StockService;
use Domain\User\Jobs\SendTelegramMessageJob;
use Domain\User\Models\User;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();

    config([
        'telegram.enabled' => true,
        'telegram.queue' => 'low',
        'telegram.channels.order' => '@boofstore_order_notification',
        'telegram.channels.error' => '@boofstore_error_notification',
    ]);
});

it('queues a telegram order notification when a user registers', function () {
    Queue::fake();
    $this->fakeRecaptchaSuccess();

    $this->postJson('/api/register', [
        'email' => 'telegram-reg@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'privacy_policy' => true,
        'token' => 'fake-recaptcha',
    ])->assertCreated();

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_order_notification'
            && str_contains($job->message, 'ثبت نام کاربر جدید')
            && str_contains($job->message, 'telegram-reg@example.com');
    });
});

it('queues a telegram order notification when an order is paid with wallet', function () {
    Queue::fake();

    $customer = User::factory()->create();
    $this->createWalletFor($customer, 99_999_999_999);

    $brand = Brand::factory()->create(['has_stock_management' => 1]);
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => User::factory(),
        'amount' => 100_000,
        'active' => 1,
        'status' => Product::COMPLETED,
        'is_failed' => 0,
    ]);
    $size = Size::factory()->create([
        'product_id' => $product->id,
        'status' => 1,
    ]);
    Container::getInstance()->make(StockService::class)->setQuantity($size->id, 10);
    Sanctum::actingAs($customer);

    $this->postJson('/api/profile/orders', [
        'products' => [
            ['id' => $product->id, 'count' => 2, 'size_id' => $size->id],
        ],
    ])->assertCreated();

    $order = Order::query()
        ->where('user_id', $customer->id)
        ->where('status', Order::PENDING)
        ->latest('id')
        ->firstOrFail();

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertCreated()->assertJsonPath('status', 1);

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) use ($order) {
        return $job->chatId === '@boofstore_order_notification'
            && str_contains($job->message, 'کیف پول')
            && str_contains($job->message, (string) $order->id);
    });
});

it('queues a telegram error notification when a critical exception is reported', function () {
    Queue::fake();

    report(new RuntimeException('critical test failure'));

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_error_notification'
            && str_contains($job->message, 'critical test failure')
            && str_contains($job->message, RuntimeException::class);
    });
});

it('does not queue telegram error notifications for client errors', function () {
    Queue::fake();

    report(new NotFoundHttpException('missing'));
    report(ValidationException::withMessages(['email' => 'invalid']));

    Queue::assertNothingPushed();
});
