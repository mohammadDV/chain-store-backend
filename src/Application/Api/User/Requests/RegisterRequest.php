<?php

namespace Application\Api\User\Requests;

use Application\Api\User\Rules\Recaptcha;
use Application\Api\User\Rules\StrongPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => StrongPassword::requiredConfirmed(),
            'privacy_policy' => ['required', 'accepted'],
            'token' => ['required', new Recaptcha(expectedAction: 'REGISTER')],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'ایمیل الزامی است',
            'email.email' => 'فرمت ایمیل صحیح نیست',
            'email.unique' => 'این ایمیل قبلا ثبت شده است',
            'password.required' => __('site.password_required'),
            'password.confirmed' => __('site.Password confirmation does not match'),
            'privacy_policy.required' => 'قبول قوانین و مقررات الزامی است',
            'privacy_policy.accepted' => 'لطفا قوانین و مقررات را بپذیرید',
        ];
    }
}
