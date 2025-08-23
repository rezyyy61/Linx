<?php

namespace App\Http\Requests\Profile\Media;

use Illuminate\Foundation\Http\FormRequest;

class ProfileFileAttachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
