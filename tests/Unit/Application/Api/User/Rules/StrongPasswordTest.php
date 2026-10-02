<?php

use Application\Api\User\Rules\StrongPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts a strong password', function () {
    expect(StrongPassword::missingRequirements('Pass123!'))->toBe([]);
});

it('lists every missing password requirement', function () {
    $missing = StrongPassword::missingRequirements('abc');

    expect($missing)->toContain(__('site.password_req_min', ['min' => StrongPassword::MIN_LENGTH]))
        ->and($missing)->toContain(__('site.password_req_uppercase'))
        ->and($missing)->toContain(__('site.password_req_number'))
        ->and($missing)->toContain(__('site.password_req_symbol'))
        ->and($missing)->not->toContain(__('site.password_req_lowercase'));
});

it('rejects passwords missing only a symbol', function () {
    $missing = StrongPassword::missingRequirements('Password1');

    expect($missing)->toBe([__('site.password_req_symbol')]);
});

it('rejects register passwords that miss complexity with an explicit message', function () {
    $this->seedRoles();
    $this->fakeRecaptchaSuccess();

    $response = $this->postJson('/api/register', [
        'email' => 'weakpass@example.com',
        'password' => 'password1',
        'password_confirmation' => 'password1',
        'privacy_policy' => true,
        'token' => 'fake-recaptcha',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['password']);

    $message = implode(' ', $response->json('errors.password') ?? []);

    expect($message)
        ->toContain('حرف بزرگ')
        ->toContain('نماد');
});
