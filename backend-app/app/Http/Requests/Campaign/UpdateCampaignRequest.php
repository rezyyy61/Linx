<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('campaign')?->id;

        return [
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'goal' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'in:draft,running,paused,ended'],
            'donation_enabled' => ['sometimes', 'boolean'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', "unique:campaigns,slug,{$id}"],

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
