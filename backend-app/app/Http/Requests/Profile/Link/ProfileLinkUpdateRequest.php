<?php

namespace App\Http\Requests\Profile\Link;

use Illuminate\Foundation\Http\FormRequest;

class ProfileLinkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'in:website,email,phone,telegram,instagram,facebook,twitter,custom'],
            'title' => ['sometimes', 'nullable', 'string', 'max:100'],
            'value' => ['sometimes', 'nullable', 'string', 'max:255'],
            'url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
