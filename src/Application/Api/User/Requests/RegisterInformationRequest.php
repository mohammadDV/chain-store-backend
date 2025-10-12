<?php

namespace Application\Api\User\Requests;

use Application\Api\User\Rules\NicknameCheck;
use Application\Api\User\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;

class RegisterInformationRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'privacy_policy' => ['required', 'accepted'],
            'mobile' => ['required', 'regex:/(09)[0-9]{9}/', 'digits:11', 'numeric', 'unique:users,mobile'],
            'nickname' => ['required', 'string', 'min:3', 'max:255', 'unique:users,nickname', new NicknameCheck],
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
            'first_name.required' => 'نام الزامی است',
            'last_name.required' => 'نام خانوادگی الزامی است',
            'privacy_policy.required' => 'قبول قوانین و مقررات الزامی است',
            'privacy_policy.accepted' => 'لطفا قوانین و مقررات را بپذیرید',
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست',
            'mobile.digits' => 'شماره موبایل باید ۱۱ رقم باشد',
            'mobile.numeric' => 'شماره موبایل باید عدد باشد',
            'mobile.unique' => 'این شماره موبایل قبلا ثبت شده است',
            'nickname.required' => 'نام کاربری الزامی است',
            'nickname.min' => 'نام کاربری باید حداقل ۳ کاراکتر باشد',
            'nickname.max' => 'نام کاربری نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'nickname.unique' => 'این نام کاربری قبلا ثبت شده است',
            'email.required' => 'ایمیل الزامی است',
            'email.email' => 'فرمت ایمیل صحیح نیست',
            'email.unique' => 'این ایمیل قبلا ثبت شده است',
            'password.required' => 'رمز عبور الزامی است',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'password.regex' => 'رمز عبور باید شامل حروف و اعداد باشد',
        ];
    }
}