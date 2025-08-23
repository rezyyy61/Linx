<?php

namespace App\Http\Requests\Profile\Media;

use Illuminate\Foundation\Http\FormRequest;

class ProfileLogoAttachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'media_id' => ['required', 'integer', 'exists:media,id'],
        ];
    }
}
