<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class CreateRepostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:public,private,followers'],
            'shareable_type' => ['nullable', 'string'],
            'shareable_alias' => ['nullable', 'string', 'max:64'],
            'shareable_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
