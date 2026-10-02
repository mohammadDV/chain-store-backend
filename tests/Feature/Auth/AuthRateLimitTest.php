<?php

use Core\Http\RateLimiting\AuthRateLimiter;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    RateLimiter::clear('login:ip:127.0.0.1');
    RateLimiter::clear('login:ip-hour:127.0.0.1');
    RateLimiter::clear('register:ip:127.0.0.1');
    RateLimiter::clear('register:ip-hour:127.0.0.1');
    RateLimiter::clear('forgot-password:ip:127.0.0.1');
});

it('rate limits login after too many attempts from the same ip', function () {
    $this->fakeRecaptchaSuccess();

    User::factory()->create([
        'email' => 'victim@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    for ($i = 0; $i < AuthRateLimiter::LOGIN_PER_MINUTE; $i++) {
        $this->postJson('/api/login', [
            'email' => 'victim@example.com',
            'password' => 'WrongPass1!',
            'token' => 'fake-recaptcha',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/login', [
        'email' => 'victim@example.com',
        'password' => 'WrongPass1!',
        'token' => 'fake-recaptcha',
    ])->assertStatus(429)
        ->assertJsonPath('status', 0);
});

it('rate limits login across different emails from the same ip', function () {
    $this->fakeRecaptchaSuccess();

    for ($i = 0; $i < AuthRateLimiter::LOGIN_PER_MINUTE; $i++) {
        $this->postJson('/api/login', [
            'email' => "user{$i}@example.com",
            'password' => 'WrongPass1!',
            'token' => 'fake-recaptcha',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/login', [
        'email' => 'another@example.com',
        'password' => 'WrongPass1!',
        'token' => 'fake-recaptcha',
    ])->assertStatus(429)
        ->assertJsonPath('status', 0);
});

it('rate limits register after too many attempts from the same ip', function () {
    $this->fakeRecaptchaSuccess();

    for ($i = 0; $i < AuthRateLimiter::REGISTER_PER_MINUTE; $i++) {
        $this->postJson('/api/register', [
            'email' => "reg{$i}@example.com",
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'privacy_policy' => true,
            'token' => 'fake-recaptcha',
        ])->assertCreated();
    }

    $this->postJson('/api/register', [
        'email' => 'blocked@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'privacy_policy' => true,
        'token' => 'fake-recaptcha',
    ])->assertStatus(429)
        ->assertJsonPath('status', 0);
});

it('allows only one forgot-password request per ip every five minutes', function () {
    Mail::fake();

    User::factory()->create(['email' => 'first@example.com']);
    User::factory()->create(['email' => 'second@example.com']);

    $this->postJson('/api/forgot-password', [
        'email' => 'first@example.com',
    ])->assertOk()->assertJsonPath('status', 1);

    $this->postJson('/api/forgot-password', [
        'email' => 'second@example.com',
    ])->assertStatus(429)
        ->assertJsonPath('status', 0);
});

it('allows another forgot-password request after the decay window', function () {
    Mail::fake();

    User::factory()->create(['email' => 'first@example.com']);
    User::factory()->create(['email' => 'second@example.com']);

    $this->postJson('/api/forgot-password', [
        'email' => 'first@example.com',
    ])->assertOk();

    $this->travel(AuthRateLimiter::FORGOT_PASSWORD_DECAY_MINUTES)->minutes();

    $this->postJson('/api/forgot-password', [
        'email' => 'second@example.com',
    ])->assertOk()->assertJsonPath('status', 1);
});
