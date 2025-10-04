<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string'],
            'kind' => ['sometimes', Rule::in(['fundraising', 'petition', 'volunteer', 'awareness'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'paused', 'completed', 'failed', 'archived'])],
            'visibility' => ['sometimes', Rule::in(['public', 'members', 'private'])],
            'owner_id' => ['sometimes', 'integer', 'min:1'],

            'starts_at_from' => ['sometimes', 'date'],
            'ends_at_to' => ['sometimes', 'date'],

            'order_by' => ['sometimes', Rule::in(['publish_at', 'created_at', 'updated_at', 'starts_at', 'ends_at', 'title', 'status', 'kind'])],
            'order_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
