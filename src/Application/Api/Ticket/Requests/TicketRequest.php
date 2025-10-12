<?php

namespace Application\Api\Ticket\Requests;

use Core\Http\Requests\BaseRequest;

class TicketRequest extends BaseRequest
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
            'subject_id' => ['required','exists:ticket_subjects,id'],
            'message' => ['required','string','min:3','max:255'],
            'file' => !empty($this->get('file')) ? ['required','string'] : ['sometimes'],
        ];
    }
}
