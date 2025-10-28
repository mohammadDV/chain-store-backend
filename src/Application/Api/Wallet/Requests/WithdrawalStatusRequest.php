<?php

namespace Application\Api\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:completed,reject'],
            'reason' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:2048'], // 2MB max
        ];
    }
}