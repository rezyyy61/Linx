<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required','string','max:50'],
            'channel' => ['required','string','max:50'],
            'title' => ['required','string','max:200'],
            'body' => ['nullable','string'],
            'schedule_at' => ['nullable','date'],
            'status' => ['nullable','in:draft,scheduled,published,paused'],
            'metrics_json' => ['nullable','array'],
        ];
    }
}
