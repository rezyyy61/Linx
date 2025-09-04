<?php

namespace App\Http\Requests\Event;

use App\Models\Event\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeEvent = $this->route('event');
        if ($routeEvent instanceof Event) {
            $id = $routeEvent->id;
        } else {
            $id = null;
        }

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
            'organizer_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:191', Rule::unique('events', 'slug')->ignore($id)],
            'publish_at' => ['sometimes', 'nullable', 'date'],

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
