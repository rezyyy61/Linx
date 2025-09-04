<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['required', 'timezone'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'organizer_id' => ['nullable', 'integer', 'exists:users,id'],
            'slug' => ['nullable', 'string', 'max:191', 'unique:events,slug'],
            'publish_at' => ['nullable', 'date'],

            'settings' => ['sometimes', 'array'],
            'settings.type' => ['sometimes', Rule::in(['in_person', 'online', 'hybrid'])],
            'settings.visibility' => ['sometimes', Rule::in(['public', 'unlisted', 'private'])],
            'settings.join_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'settings.join_platform' => ['sometimes', 'nullable', 'string', 'max:32'],
            'settings.join_passcode' => ['sometimes', 'nullable', 'string', 'max:128'],
            'settings.join_instructions' => ['sometimes', 'nullable', 'string'],
            'settings.join_visible_minutes_before' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10080'],
            'settings.access_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.og_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.og_description' => ['sometimes', 'nullable', 'string'],

            'covers' => ['sometimes', 'array'],
            'covers.*.id' => ['required', 'integer', 'exists:media,id'],
            'covers.*.order' => ['sometimes', 'integer', 'min:0'],
            'documents' => ['sometimes', 'array'],
            'documents.*.id' => ['required', 'integer', 'exists:media,id'],
            'documents.*.order' => ['sometimes', 'integer', 'min:0'],
            'cover_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
        ];
    }
}
