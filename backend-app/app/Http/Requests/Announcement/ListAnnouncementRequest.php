<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class ListAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'visibility' => ['sometimes', 'in:public,members,supporters,private'],
            'pinned' => ['sometimes', 'boolean'],
            'published_from' => ['sometimes', 'date'],
            'published_to' => ['sometimes', 'date'],
            'order_by' => ['sometimes', 'in:publish_at,created_at,updated_at,is_pinned'],
            'order_dir' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
