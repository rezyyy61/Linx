<?php

declare(strict_types=1);

namespace App\Http\Requests\Publication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'issue' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('publications', 'slug')],
            'is_published' => ['sometimes', 'boolean'],
            'publish_at' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'max:8'],
            'cover_id' => ['nullable', 'integer', 'exists:media,id'],
            'documents' => ['nullable', 'array'],
            'documents.*.id' => ['required_with:documents', 'integer', 'exists:media,id'],
            'documents.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_published')) {
            $this->merge(['is_published' => filter_var($this->input('is_published'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false]);
        }
    }
}
