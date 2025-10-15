<?php

namespace Application\Api\Product\Requests;

use Core\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SearchProductRequest extends BaseRequest
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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'integer', 'exists:categories,id'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['nullable', 'integer', 'exists:brands,id'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['nullable', 'integer', 'exists:colors,id'],
            'start_amount' => ['nullable', 'numeric'],
            'end_amount' => ['nullable', 'numeric'],
            'query' => ['nullable', 'string', 'min:1', 'max:50'],
            'column' => ['nullable', 'string', 'min:2', 'max:50'],
            'sort' => ['nullable', 'string', 'in:desc,asc'],
            'page' => ['nullable','integer'],
            'count' => ['nullable','integer', 'min:5','max:200']
        ];
    }
}