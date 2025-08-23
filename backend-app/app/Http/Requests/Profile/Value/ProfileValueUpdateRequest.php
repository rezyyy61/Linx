<?php

namespace App\Http\Requests\Profile\Value;

use Illuminate\Foundation\Http\FormRequest;

class ProfileValueUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'in:predefined,custom'],
            'value' => ['sometimes', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
