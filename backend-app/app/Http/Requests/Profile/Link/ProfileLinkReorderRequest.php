<?php

namespace App\Http\Requests\Profile\Link;

use Illuminate\Foundation\Http\FormRequest;

class ProfileLinkReorderRequest extends FormRequest
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
