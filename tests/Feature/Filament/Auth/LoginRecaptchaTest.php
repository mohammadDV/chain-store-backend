<?php

use App\Filament\Pages\Auth\Login;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('rejects filament login without recaptcha token', function () {
    User::factory()->admin()->create([
        'email' => 'admin-captcha@example.com',
        'password' => Hash::make('Password1!'),
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin-captcha@example.com',
            'password' => 'Password1!',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['token']);
});

it('rejects filament login when recaptcha fails', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
    ]);

    User::factory()->admin()->create([
        'email' => 'admin-bad-captcha@example.com',
        'password' => Hash::make('Password1!'),
        'status' => 1,
        'email_verified_at' => now(),
        'verified_at' => now(),
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin-bad-captcha@example.com',
            'password' => 'Password1!',
            'token' => 'bad-token',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['token']);
});

it('allows filament login with valid credentials and recaptcha', function () {
    $this->fakeRecaptchaSuccess();

    $admin = User::factory()->admin()->create([
        'email' => 'admin-ok@example.com',
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
            'email' => 'admin-ok@example.com',
            'password' => 'Password1!',
            'token' => 'fake-recaptcha',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticatedAs($admin);
});
