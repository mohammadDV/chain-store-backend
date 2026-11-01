<?php

namespace Application\Api\Product\Requests;

use Core\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UnlockOrderRecipientRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'max:255'],
            'unlock_token' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $password = $this->input('password');
            $token = $this->input('unlock_token');

            if (! filled($password) && ! filled($token)) {
                $validator->errors()->add('password', __('validation.required', ['attribute' => 'password']));
            }
        });
    }
}
