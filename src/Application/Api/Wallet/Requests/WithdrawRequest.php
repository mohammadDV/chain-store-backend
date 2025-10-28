<?php

namespace Application\Api\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10000'],
            'description' => ['required', 'string', 'max:255'],
            'card' => ['nullable', 'string', 'max:32'],
            'sheba' => ['nullable', 'string', 'max:32'],
        ];
    }
}