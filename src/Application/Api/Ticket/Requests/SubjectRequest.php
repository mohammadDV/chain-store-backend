<?php

namespace Application\Api\Ticket\Requests;

use Core\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SubjectRequest extends BaseRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
