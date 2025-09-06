<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes','string','max:50'],
            'channel' => ['sometimes','string','max:50'],
            'title' => ['sometimes','string','max:200'],
            'body' => ['sometimes','nullable','string'],
            'schedule_at' => ['sometimes','nullable','date'],
            'published_at' => ['sometimes','nullable','date'],
            'status' => ['sometimes','in:draft,scheduled,published,paused'],
            'metrics_json' => ['sometimes','nullable','array'],
        ];
    }
}
