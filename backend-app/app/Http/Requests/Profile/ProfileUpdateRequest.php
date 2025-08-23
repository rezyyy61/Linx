<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $profileId = optional($this->user()->profile)->id;

        return [
            'slug' => [
                'sometimes',
                'filled',
                'alpha_dash',
                'min:2',
                'max:100',
                Rule::unique('profiles', 'slug')->ignore($profileId),
            ],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'founded_year' => ['sometimes', 'nullable', 'digits:4', 'integer', 'between:1800,2100'],
            'avatar_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'translation' => ['sometimes', 'array'],
            'translation.locale' => ['sometimes', 'string', 'max:8'],
            'translation.tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'translation.about' => ['sometimes', 'nullable', 'string'],
            'translation.goals' => ['sometimes', 'nullable', 'string'],
            'translation.activities' => ['sometimes', 'nullable', 'string'],
            'translation.structure' => ['sometimes', 'nullable', 'string'],

            'entity_type' => ['prohibited'],
            'user_id' => ['prohibited'],
            'verified' => ['prohibited'],
            'published_at' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
