<?php

use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $user = User::factory()->create();
    $notification = Notification::query()->create([
        'user_id' => $user->id,
        'title' => 'Order paid',
        'content' => 'Your order was paid',
        'status' => 1,
        'read' => 0,
    ]);

    expect($notification->user())->toBeInstanceOf(BelongsTo::class)
        ->and($notification->user->is($user))->toBeTrue();
});

it('casts status and read to boolean', function () {
    $notification = Notification::query()->create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Unread',
        'content' => null,
        'status' => 1,
        'read' => 0,
        'model_id' => 12,
        'model_type' => 'order',
    ]);

    expect($notification->status)->toBeTrue()
        ->and($notification->read)->toBeFalse()
        ->and($notification->model_id)->toBe(12)
        ->and($notification->model_type)->toBe('order');
});

it('can mark notifications as read', function () {
    $notification = Notification::query()->create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Hello',
        'content' => 'World',
        'status' => 1,
        'read' => 0,
    ]);

    $notification->update(['read' => 1]);

    expect($notification->fresh()->read)->toBeTrue();
});
