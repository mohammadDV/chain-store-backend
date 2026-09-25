<?php

use Application\Api\User\Mail\PasswordResetMail;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('registers a user with wallet and sanctum token', function () {
    $response = $this->postJson('/api/register', [
        'email' => 'new@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 1)
        ->assertJsonStructure(['token', 'user', 'customer_number']);

    $user = User::where('email', 'new@example.com')->first();
    expect($user)->not->toBeNull();
    expect(Wallet::where('user_id', $user->id)->where('currency', Wallet::IRR)->exists())->toBeTrue();
    expect($user->tokens()->count())->toBe(1);
});

it('rejects duplicate email on register', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/api/register', [
        'email' => 'dup@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertStatus(422);
});

it('rejects weak password on register', function () {
    $this->postJson('/api/register', [
        'email' => 'weak@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertStatus(422);
});

it('logs in with valid credentials and recaptcha', function () {
    $this->fakeRecaptchaSuccess();

    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('Password1!'),
        'status' => 1,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'Password1!',
        'token' => 'fake-recaptcha',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonStructure(['token', 'user']);
});

it('rejects login with wrong password', function () {
    $this->fakeRecaptchaSuccess();

    User::factory()->create([
        'email' => 'badpass@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'badpass@example.com',
        'password' => 'WrongPass1!',
        'token' => 'fake-recaptcha',
    ])->assertUnauthorized()
        ->assertJsonPath('status', 0);
});

it('rejects login when recaptcha fails', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
    ]);

    User::factory()->create([
        'email' => 'captcha@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'captcha@example.com',
        'password' => 'Password1!',
        'token' => 'bad-token',
    ])->assertStatus(422);
});

it('completes registration profile for authenticated user', function () {
    $user = User::factory()->create([
        'verified_at' => null,
        'first_name' => null,
        'nickname' => null,
        'mobile' => null,
    ]);
    $this->actingAsUser($user);

    $response = $this->postJson('/api/complete-register', [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'privacy_policy' => true,
        'mobile' => '09121234567',
        'nickname' => 'alirezaei',
    ]);

    $response->assertCreated()->assertJsonPath('status', 1);
    expect($user->fresh()->verified_at)->not->toBeNull();
    expect($user->fresh()->nickname)->toBe('alirezaei');
});

it('rejects complete-register for guests', function () {
    $this->postJson('/api/complete-register', [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'privacy_policy' => true,
        'mobile' => '09121234567',
        'nickname' => 'guestnick',
    ])->assertUnauthorized();
});

it('rejects nickname containing admin', function () {
    $user = User::factory()->create(['verified_at' => null, 'nickname' => null, 'mobile' => null]);
    $this->actingAsUser($user);

    $this->postJson('/api/complete-register', [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'privacy_policy' => true,
        'mobile' => '09121234568',
        'nickname' => 'superadmin',
    ])->assertStatus(422);
});

it('logs out and deletes all tokens', function () {
    $user = User::factory()->create();
    $token = $user->createToken('chainstoretoken')->plainTextToken;
    expect($user->tokens()->count())->toBe(1);

    $this->withToken($token)
        ->getJson('/api/logout')
        ->assertCreated()
        ->assertJsonPath('status', 1);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('sends password reset mail for known email', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/forgot-password', [
        'email' => 'reset@example.com',
    ])->assertOk()->assertJsonPath('status', 1);

    Mail::assertSent(PasswordResetMail::class);
    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeTrue();
});

it('resets password with valid token', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset2@example.com', 'password' => Hash::make('OldPass1!')]);

    $plain = 'plain-reset-token';
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($plain),
        'created_at' => now(),
    ]);

    $this->postJson('/api/verify-reset-token', [
        'token' => $plain,
        'email' => $user->email,
    ])->assertOk()->assertJsonPath('status', 1);

    $this->postJson('/api/reset-password', [
        'token' => $plain,
        'email' => $user->email,
        'password' => 'NewPass1!',
        'password_confirmation' => 'NewPass1!',
    ])->assertOk()->assertJsonPath('status', 1);

    expect(Hash::check('NewPass1!', $user->fresh()->password))->toBeTrue();
    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeFalse();
});

it('rejects expired reset token', function () {
    $user = User::factory()->create(['email' => 'expired@example.com']);
    $plain = 'expired-token';
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($plain),
        'created_at' => now()->subHours(2),
    ]);

    $this->postJson('/api/reset-password', [
        'token' => $plain,
        'email' => $user->email,
        'password' => 'NewPass1!',
        'password_confirmation' => 'NewPass1!',
    ])->assertStatus(400)->assertJsonPath('status', 0);
});

it('verifies email via signed url', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $this->get($url)->assertRedirect('/auth/check-verification');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects google verify dead endpoint with not found', function () {
    $this->postJson('/api/google/verify', [])->assertNotFound();
});

it('creates wallet and user via google oauth callback', function () {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'access-token',
            'token_type' => 'Bearer',
        ], 200),
        'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
            'email' => 'google@example.com',
            'name' => 'Google User',
            'sub' => 'google-sub-1',
        ], 200),
    ]);

    $this->get('/api/auth/google/callback?code=auth-code')
        ->assertRedirect('/auth/check-verification');

    $user = User::where('email', 'google@example.com')->first();
    expect($user)->not->toBeNull();
    expect(Wallet::where('user_id', $user->id)->exists())->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
});
