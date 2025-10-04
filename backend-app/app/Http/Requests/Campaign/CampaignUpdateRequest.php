<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Campaign\Campaign|null $bound */
        $bound = $this->route('campaign');
        $id = $bound?->id;

        return [
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],

            'title' => ['sometimes', 'string', 'max:200'],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],

            'kind' => ['sometimes', Rule::in(['fundraising', 'petition', 'volunteer', 'awareness'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'paused', 'completed', 'failed', 'archived'])],
            'visibility' => ['sometimes', Rule::in(['public', 'members', 'private'])],

            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'publish_at' => ['sometimes', 'nullable', 'date'],

            'slug' => ['sometimes', 'nullable', 'string', 'max:200', Rule::unique('campaigns', 'slug')->ignore($id)],

            // media
            'cover_id' => ['sometimes', 'nullable', 'integer'],
            'documents' => ['sometimes', 'array'],
            'documents.*.id' => ['required', 'integer'],
            'documents.*.order' => ['sometimes', 'integer'],

            // meta object (preferred)
            'meta' => ['sometimes', 'array'],

            // flat meta fields (compat)
            'goal_amount' => ['sometimes', 'numeric', 'min:0'],
            'goal_currency' => ['sometimes', 'string', 'max:8'],
            'raised_amount' => ['sometimes', 'numeric', 'min:0'],
            'signature_goal' => ['sometimes', 'integer', 'min:0'],
            'signatures_count' => ['sometimes', 'integer', 'min:0'],
            'needed_slots' => ['sometimes', 'integer', 'min:0'],
            'filled_slots' => ['sometimes', 'integer', 'min:0'],
            'target_reach' => ['sometimes', 'integer', 'min:0'],
            'current_reach' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        /** @var \App\Models\Campaign\Campaign|null $bound */
        $bound = $this->route('campaign');

        $fallbackKind = $bound ? (string) $bound->kind : '';
        $kind = (string) ($data['kind'] ?? $fallbackKind);

        if (empty($data['meta']) || ! is_array($data['meta'])) {
            $meta = [];

            switch ($kind) {
                case 'fundraising':
                    foreach (['goal_amount', 'goal_currency', 'raised_amount'] as $k) {
                        if (array_key_exists($k, $data)) {
                            $meta[$k] = $data[$k];
                        }
                    }
                    break;

                case 'petition':
                    foreach (['signature_goal', 'signatures_count'] as $k) {
                        if (array_key_exists($k, $data)) {
                            $meta[$k] = $data[$k];
                        }
                    }
                    break;

                case 'volunteer':
                    foreach (['needed_slots', 'filled_slots'] as $k) {
                        if (array_key_exists($k, $data)) {
                            $meta[$k] = $data[$k];
                        }
                    }
                    break;

                case 'awareness':
                    foreach (['target_reach', 'current_reach'] as $k) {
                        if (array_key_exists($k, $data)) {
                            $meta[$k] = $data[$k];
                        }
                    }
                    break;
            }

            if ($meta !== []) {
                $data['meta'] = $meta;
            }
        }

        return $data;
    }
}
