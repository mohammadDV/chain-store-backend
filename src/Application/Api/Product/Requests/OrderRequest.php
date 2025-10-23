<?php

namespace Application\Api\Product\Requests;

use Core\Http\Requests\BaseRequest;

class OrderRequest extends BaseRequest
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
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.count' => ['required', 'integer', 'min:1'],
            'products.*.color_id' => ['nullable', 'exists:colors,id'],
            'products.*.size_id' => ['nullable', 'exists:sizes,id'],
            'discount_code' => ['nullable', 'string', 'exists:discounts,code'],
        ];
    }
}
