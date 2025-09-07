<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class ListCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:draft,running,paused,ended'],
            'starts_from' => ['sometimes', 'date'],
            'starts_to' => ['sometimes', 'date'],
            'order_by' => ['sometimes', 'in:starts_at,created_at,updated_at'],
            'order_dir' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
