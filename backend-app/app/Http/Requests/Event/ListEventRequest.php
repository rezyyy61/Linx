<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_published')) {
            $raw = $this->input('is_published');
            $bool = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                $this->merge(['is_published' => $bool]);
            }
        }

        foreach (['per_page', 'page'] as $k) {
            if ($this->has($k)) {
                $this->merge([$k => (int) $this->input($k)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
            'starts_from' => ['sometimes', 'date'],
            'starts_to' => ['sometimes', 'date', 'after_or_equal:starts_from'],
            'order_by' => ['sometimes', Rule::in(['starts_at', 'created_at', 'updated_at'])],
            'order_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
