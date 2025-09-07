<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:announcements,slug'],
            'is_pinned' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'in:public,members,supporters,private'],
            'publish_at' => ['sometimes', 'nullable', 'date'],
            'cover_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'covers' => ['sometimes', 'array'],
            'covers.*.id' => ['required_with:covers', 'integer', 'exists:media,id'],
            'covers.*.order' => ['sometimes', 'integer', 'min:0'],
            'documents' => ['sometimes', 'array'],
            'documents.*.id' => ['required_with:documents', 'integer', 'exists:media,id'],
            'documents.*.order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
