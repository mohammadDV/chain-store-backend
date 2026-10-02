<?php

namespace Application\Api\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Shared strong-password policy for register, reset, and change-password.
 */
class StrongPassword implements ValidationRule
{
    public const MIN_LENGTH = 8;

    /**
     * Allowed symbol characters (must include at least one).
     */
    public const SYMBOL_PATTERN = '/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $missing = self::missingRequirements((string) $value);

        if ($missing === []) {
            return;
        }

        $fail(__('site.password_missing_requirements', [
            'requirements' => implode('، ', $missing),
        ]));
    }

    /**
     * @return list<string>
     */
    public static function missingRequirements(string $password): array
    {
        $missing = [];

        if (mb_strlen($password) < self::MIN_LENGTH) {
            $missing[] = __('site.password_req_min', ['min' => self::MIN_LENGTH]);
        }

        if (! preg_match('/[a-z]/u', $password)) {
            $missing[] = __('site.password_req_lowercase');
        }

        if (! preg_match('/[A-Z]/u', $password)) {
            $missing[] = __('site.password_req_uppercase');
        }

        if (! preg_match('/\d/u', $password)) {
            $missing[] = __('site.password_req_number');
        }

        if (! preg_match(self::SYMBOL_PATTERN, $password)) {
            $missing[] = __('site.password_req_symbol');
        }

        return $missing;
    }

    /**
     * @return list<ValidationRule|string>
     */
    public static function requiredConfirmed(): array
    {
        return ['required', 'string', 'confirmed', new self];
    }

    /**
     * @return list<ValidationRule|string>
     */
    public static function optional(): array
    {
        return ['nullable', 'string', new self];
    }
}
