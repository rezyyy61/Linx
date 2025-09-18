<?php

declare(strict_types=1);

namespace App\Http\Requests\Share;

use App\Enums\Share\ShareChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shareable_type' => ['nullable', 'string'],
            'shareable_alias' => ['nullable', 'string', 'max:64'],
            'shareable_id' => ['required', 'integer', 'min:1'],
            'channel' => ['required', new Enum(ShareChannel::class)],
            'utm_source' => ['nullable', 'string', 'max:64'],
            'utm_medium' => ['nullable', 'string', 'max:64'],
            'utm_campaign' => ['nullable', 'string', 'max:128'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
