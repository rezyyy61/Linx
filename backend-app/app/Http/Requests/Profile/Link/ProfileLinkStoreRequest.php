<?php

namespace App\Http\Requests\Profile\Link;

use Illuminate\Foundation\Http\FormRequest;

class ProfileLinkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:website,email,phone,telegram,instagram,facebook,twitter,custom'],
            'title' => ['nullable', 'string', 'max:100'],
            'value' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
