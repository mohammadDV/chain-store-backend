<?php

namespace Application\Api\Product\Requests;

use Core\Http\Requests\BaseRequest;

class ProductCategoryRequest extends BaseRequest
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
            'title'          => ['required', 'string', 'max:255'],
            'status'         => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}
