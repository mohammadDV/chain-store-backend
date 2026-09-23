<?php

namespace Application\Api\Payment\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentRequest extends FormRequest
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
            'amount' => ['nullable', 'required_if:type,wallet', 'numeric', 'min:1000'],
            'type' => ['required', 'string', 'in:wallet,identity'],
            'image' => ['required', 'string'],
        ];
    }
}
