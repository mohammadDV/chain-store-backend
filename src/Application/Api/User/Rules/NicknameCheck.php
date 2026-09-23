<?php

namespace Application\Api\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class NicknameCheck implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $words = ['admin', 'ادمین'];

        if (Str::contains($value, $words)) {
            // The string contains at least one of the words
            $fail(trans('site.Invalid Nickname'));
        }
    }
}
