<?php

namespace Application\Api\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class ValidateRole implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (!Auth::check() || Auth::user()->level != 3) {
            $fail(__('site.Invalid request'));
        }

        // if (Auth::check() && Auth::user()->is_admin != 3 && $value != 4) {
        //     $fail(__('site.Invalid request'));
        // }

    }
}
