<?php

namespace App\Http\Requests\Member;

use App\Enums\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(ContentType::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
