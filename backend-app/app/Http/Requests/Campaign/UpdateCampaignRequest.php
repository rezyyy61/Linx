<?php

declare(strict_types=1);

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
        return [
            'title' => ['sometimes','string','max:160'],
            'slug' => ['sometimes','nullable','string','max:160'],
            'goal' => ['sometimes','nullable','string','max:255'],
            'description' => ['sometimes','nullable','string'],
            'status' => ['sometimes','in:draft,running,paused,ended'],
            'starts_at' => ['sometimes','nullable','date'],
            'ends_at' => ['sometimes','nullable','date','after_or_equal:starts_at'],
        ];
    }
}
