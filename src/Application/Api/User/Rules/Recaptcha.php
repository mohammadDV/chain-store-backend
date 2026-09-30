<?php

namespace Application\Api\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $gResponseToken = is_string($value) ? trim($value) : '';

        if ($gResponseToken === '') {
            $fail(trans('site.Invalid recaptcha'));

            return;
        }

        $response = Http::asForm()->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $gResponseToken,
            ]
        );

        $payload = json_decode($response->body(), true);
        $success = is_array($payload) && ($payload['success'] ?? false) === true;

        if (! $success) {
            $fail(trans('site.Invalid recaptcha'));
        }
    }
}
