<?php

namespace Application\Api\User\Requests;

use Application\Api\User\Rules\StrongPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            'password' => StrongPassword::requiredConfirmed(),
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => __('site.password_required'),
            'password.confirmed' => __('site.Password confirmation does not match'),
        ];
    }
}
