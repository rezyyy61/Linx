<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required','string','max:160'],
            'slug' => ['nullable','string','max:160'],
            'goal' => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'status' => ['nullable','in:draft,running,paused,ended'],
            'starts_at' => ['nullable','date'],
            'ends_at' => ['nullable','date','after_or_equal:starts_at'],
        ];
    }
}
