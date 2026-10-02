<?php

use App\Filament\Pages\Auth\Login;
use Domain\User\Jobs\SendTelegramMessageJob;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();

    config([
        'telegram.enabled' => true,
        'telegram.queue' => 'low',
        'telegram.channels.error' => '@boofstore_error_notification',
        'admin.login.max_attempts' => 3,
        'admin.login.decay_seconds' => 900,
    ]);

    RateLimiter::clear(
        'livewire-rate-limiter:'.sha1(Login::class.'|authenticate|'.request()->ip())
    );
});

it('renders remember me on the admin login form', function () {
    livewire(Login::class)
        ->assertFormFieldExists('email')
        ->assertFormFieldExists('password')
        ->assertFormFieldExists('remember');
});

it('queues a telegram alert when admin login credentials are wrong', function () {
    Queue::fake();
    $this->fakeRecaptchaSuccess();

    User::factory()->admin()->create([
        'email' => 'admin-fail@example.com',
        'password' => Hash::make('Password1!'),
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin-fail@example.com',
            'password' => 'WrongPass1!',
            'token' => 'fake-recaptcha',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_error_notification'
            && str_contains($job->message, 'Admin login failed')
            && str_contains($job->message, 'admin-fail@example.com')
            && ! str_contains(strtolower($job->message), 'password1')
            && ! str_contains(strtolower($job->message), 'wrongpass');
    });
});

it('rate limits admin login after three attempts and alerts telegram once', function () {
    Queue::fake();
    $this->fakeRecaptchaSuccess();

    User::factory()->admin()->create([
        'email' => 'admin-throttle@example.com',
        'password' => Hash::make('Password1!'),
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]);

    for ($i = 0; $i < 3; $i++) {
        livewire(Login::class)
            ->fillForm([
                'email' => 'admin-throttle@example.com',
                'password' => 'WrongPass1!',
                'token' => 'fake-recaptcha',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    }

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin-throttle@example.com',
            'password' => 'WrongPass1!',
            'token' => 'fake-recaptcha',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertNoRedirect();

    $this->assertGuest();

    $rateLimitAlerts = Queue::pushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return str_contains($job->message, 'Admin login rate limited');
    });

    expect($rateLimitAlerts)->toHaveCount(1);
});

it('counts failed recaptcha toward the admin login rate limit', function () {
    Queue::fake();

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
        'https://recaptchaenterprise.googleapis.com/v1/projects/*' => Http::response([
            'tokenProperties' => ['valid' => false],
        ], 200),
    ]);

    for ($i = 0; $i < 3; $i++) {
        livewire(Login::class)
            ->fillForm([
                'email' => 'admin-captcha-limit@example.com',
                'password' => 'Password1!',
                'token' => 'bad-token',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    }

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin-captcha-limit@example.com',
            'password' => 'Password1!',
            'token' => 'bad-token',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();

    Queue::assertPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return str_contains($job->message, 'Admin login rate limited');
    });
});

it('still allows a valid admin login under the hardened limit', function () {
    Queue::fake();
    $this->fakeRecaptchaSuccess();

    $admin = User::factory()->admin()->create([
        'email' => 'admin-ok-limit@example.com',
        'password' => Hash::make('Password1!'),
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]);

    config([
        'admin.super_admin_emails' => [strtolower($admin->email)],
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin-ok-limit@example.com',
            'password' => 'Password1!',
            'token' => 'fake-recaptcha',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticatedAs($admin);

    Queue::assertNotPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return str_contains($job->message, 'Admin login failed')
            || str_contains($job->message, 'Admin login rate limited');
    });
});
