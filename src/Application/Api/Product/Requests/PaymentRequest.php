<?php

namespace Application\Api\Product\Requests;

use Core\Http\Requests\BaseRequest;

class PaymentRequest extends BaseRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:wallet,bank'],
            'discount_code' => ['nullable', 'string', 'exists:discounts,code'],
            'address' => ['required', 'string'],
            'fullname' => ['required', 'string'],
            'postal_code' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
