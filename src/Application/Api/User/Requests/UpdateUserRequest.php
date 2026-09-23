<?php

namespace Application\Api\User\Requests;

use Application\Api\User\Rules\NicknameCheck;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'min:3', 'max:255', new NicknameCheck],
            'biography' => ['nullable', 'string', 'max:255'],
            'profile_photo_path' => ['nullable', 'string'],
            'bg_photo_path' => ['nullable', 'string'],
            'mobile' => ['required', 'string', 'min:11', 'max:15'],
            'status' => ['required', 'in:0,1'],
        ];
    }
}
