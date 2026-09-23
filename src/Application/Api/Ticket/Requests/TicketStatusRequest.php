<?php

namespace Application\Api\Ticket\Requests;

use Core\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class TicketStatusRequest extends BaseRequest
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
            'status' => ['required', 'in:active,closed'],
        ];
    }
}
