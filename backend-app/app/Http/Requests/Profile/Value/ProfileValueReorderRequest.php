<?php

namespace App\Http\Requests\Profile\Value;

use Illuminate\Foundation\Http\FormRequest;

class ProfileValueReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }
}
