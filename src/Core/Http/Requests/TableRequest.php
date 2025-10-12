<?php

namespace Core\Http\Requests;

class TableRequest extends BaseRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'query' => !empty($this->get('query')) ? ['required', 'string', 'min:1', 'max:50'] : 'sometimes',
            'column' => !empty($this->get('column')) ? ['required', 'string', 'min:2', 'max:50'] : 'sometimes',
            'sort' => !empty($this->get('sort')) ? ['required', 'string', 'in:desc,asc'] : 'sometimes',
            'page' => !empty($this->get('page')) ? ['required','integer'] : 'sometimes',
            'search' => !empty($this->get('search')) ? ['required','string', 'max:255'] : 'sometimes',
            'count' => !empty($this->get('count')) ? ['required','integer', 'min:5','max:200'] : 'sometimes',
            'type' => ['nullable','string','max:100'],
            'status' => ['nullable','string','max:100'],
            'categories' => ['nullable','array'],
            'categories.*' => ['nullable','integer','exists:categories,id'],
        ];
    }
}
