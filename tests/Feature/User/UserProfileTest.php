<?php

use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('returns authenticated user profile', function () {
    $user = $this->actingAsUser(User::factory()->create([
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'nickname' => 'alirezaei',
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]));

    $this->getJson('/api/profile/my-info')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.nickname', 'alirezaei');
});

it('updates authenticated user profile', function () {
    $user = $this->actingAsUser(User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'nickname' => 'oldnick',
        'mobile' => '09120000000',
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]));

    $this->patchJson('/api/profile/users', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'nickname' => 'newnick99',
        'mobile' => '09121112233',
        'biography' => 'Hello world',
    ])->assertOk()
        ->assertJsonPath('status', 1);

    expect($user->fresh()->first_name)->toBe('New')
        ->and($user->fresh()->nickname)->toBe('newnick99')
        ->and($user->fresh()->mobile)->toBe('09121112233');
});

it('rejects invalid profile update payload', function () {
    $this->actingAsUser();

    $this->patchJson('/api/profile/users', [
        'first_name' => 'A',
        'last_name' => 'B',
        'nickname' => 'adminuser',
        'mobile' => '123',
    ])->assertStatus(422);
});

it('rejects guest profile access', function () {
    $this->getJson('/api/profile/my-info')->assertUnauthorized();
});

it('returns public user info', function () {
    $user = User::factory()->create([
        'nickname' => 'publicuser',
        'status' => 1,
    ]);

    $this->getJson("/api/user-info/{$user->id}")
        ->assertOk()
        ->assertJsonStructure(['user']);
});

it('returns dashboard info for authenticated user', function () {
    $this->actingAsUser();

    $this->getJson('/api/profile/dashboard-info')
        ->assertOk()
        ->assertJsonStructure([
            'tickets',
            'order_in_progress_count',
            'order_cancelled_count',
            'order_delivered_amount',
        ]);
});

it('rejects change password with incorrect current password without duplicating full suite', function () {
    $user = $this->actingAsUser(User::factory()->create([
        'password' => Hash::make('Oldpassword123!'),
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]));

    $this->patchJson('/api/profile/users/change-password', [
        'current_password' => 'WrongPass1!',
        'password' => 'Newpassword123!',
        'password_confirmation' => 'Newpassword123!',
    ])->assertOk()
        ->assertJsonPath('status', 0);
});
