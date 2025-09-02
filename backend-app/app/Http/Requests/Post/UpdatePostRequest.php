<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use App\Enums\MediaStatus;
use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'nullable', 'string'],
            'visibility' => ['sometimes', 'nullable', 'in:public,friends,private'],
            'status' => ['sometimes', 'nullable', 'in:published,draft'],
            'media' => ['sometimes', 'nullable', 'array', 'max:10'],
            'media.*.id' => ['required_with:media', 'integer', 'exists:media,id'],
            'media.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $hasContentKey = $this->has('content');
            $hasMediaKey = $this->has('media');
            if (! $hasContentKey && ! $hasMediaKey && ! $this->has('visibility') && ! $this->has('status')) {
                $v->errors()->add('payload', 'no changes provided');
            }
            if ($this->has('media')) {
                $media = $this->input('media', []);
                $ids = collect($media)->pluck('id')->filter()->unique()->values();
                if ($ids->isNotEmpty()) {
                    $readyIds = Media::query()
                        ->whereIn('id', $ids)
                        ->where('status', MediaStatus::READY)
                        ->pluck('id')
                        ->values();
                    if ($readyIds->count() !== $ids->count()) {
                        $v->errors()->add('media', 'all media must be READY');
                    }
                }
            }
        });
    }
}
