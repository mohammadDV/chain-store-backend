<?php

namespace Application\Api\Product\Requests;

use Core\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckOrderCodeRequest extends BaseRequest
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
            'code' => ['required', 'string', 'exists:orders,code'],
        ];
    }
}
