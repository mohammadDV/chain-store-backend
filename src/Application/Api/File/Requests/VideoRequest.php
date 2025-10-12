<?php

namespace Application\Api\File\Requests;

use Core\Http\Requests\BaseRequest;

class VideoRequest extends BaseRequest
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
            'video' => 'required|mimes:mp4,ogx,oga,ogv,ogg,webm,mov|max:204800',
        ];
    }
}
