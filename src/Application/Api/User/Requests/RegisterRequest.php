<?php

namespace Application\Api\User\Requests;

use Application\Api\User\Rules\NicknameCheck;
use Application\Api\User\Rules\Recaptcha;
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'string', 'min:8', 'regex:/^[a-zA-Z0-9_!@#$%^&*-]+$/'],
            // 'token' => [new Recaptcha],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required' => 'ایمیل الزامی است',
            'email.email' => 'فرمت ایمیل صحیح نیست',
            'email.unique' => 'این ایمیل قبلا ثبت شده است',
            'password.required' => 'رمز عبور الزامی است',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'password.regex' => 'رمز عبور باید شامل حروف و اعداد باشد',
        ];
    }
}
