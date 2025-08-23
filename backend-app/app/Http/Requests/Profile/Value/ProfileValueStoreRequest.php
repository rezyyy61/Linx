<?php

namespace App\Http\Requests\Profile\Value;

use Illuminate\Foundation\Http\FormRequest;

class ProfileValueStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:predefined,custom'],
            'value' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
