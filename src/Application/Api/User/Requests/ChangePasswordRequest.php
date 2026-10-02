<?php

namespace Application\Api\User\Requests;

use Application\Api\User\Rules\StrongPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string'],
            'password' => StrongPassword::requiredConfirmed(),
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => __('site.Current password is required'),
            'password.required' => __('site.New password is required'),
            'password.confirmed' => __('site.Password confirmation does not match'),
        ];
    }
}
