<?php

use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('lists profile notifications for authenticated user', function () {
    $user = $this->actingAsUser();
    $other = User::factory()->create();

    Notification::query()->create([
        'title' => 'Your ticket updated',
        'content' => 'An operator replied',
        'user_id' => $user->id,
        'status' => 1,
        'read' => 0,
        'model_id' => 1,
        'model_type' => 'ticket',
    ]);

    Notification::query()->create([
        'title' => 'Other user notice',
        'content' => 'Hidden',
        'user_id' => $other->id,
        'status' => 1,
        'read' => 0,
        'model_id' => 2,
        'model_type' => 'ticket',
    ]);

    $response = $this->getJson('/api/profile/notifications')->assertOk();

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Your ticket updated')
        ->and($titles)->not->toContain('Other user notice');
});

it('returns unread notifications', function () {
    $user = $this->actingAsUser();

    Notification::query()->create([
        'title' => 'Unread one',
        'content' => 'Hello',
        'user_id' => $user->id,
        'status' => 1,
        'read' => 0,
    ]);

    $this->getJson('/api/profile/notifications-unread')
        ->assertOk();
});

it('marks all notifications as read', function () {
    $user = $this->actingAsUser();

    Notification::query()->create([
        'title' => 'To read',
        'content' => 'Hello',
        'user_id' => $user->id,
        'status' => 1,
        'read' => 0,
    ]);

    $this->getJson('/api/profile/notifications-read-all')
        ->assertOk()
        ->assertJsonPath('status', 1);

    expect(Notification::where('user_id', $user->id)->where('read', 0)->count())->toBe(0);
});

it('rejects guest access to notifications', function () {
    $this->getJson('/api/profile/notifications')->assertUnauthorized();
});
