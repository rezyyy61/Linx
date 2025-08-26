<?php

namespace App\Http\Requests\Media;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePresignedUrlRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'extension' => 'required|string|max:10',
            'type' => ['required', Rule::in(array_map(fn ($c) => $c->value, MediaType::cases()))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = strtolower((string) $this->input('type', ''));
        if (str_contains($type, '/')) {
            $type = explode('/', $type)[0];
        }
        $this->merge(['type' => $type]);
    }
}
