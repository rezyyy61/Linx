<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use App\Enums\MediaStatus;
use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string'],
            'visibility' => ['nullable', 'in:public,friends,private'],
            'status' => ['nullable', 'in:published,draft'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*.id' => ['required_with:media', 'integer', 'exists:media,id'],
            'media.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('media')) {
            $this->merge(['media' => null]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $hasContent = filled($this->input('content'));
            $media = $this->input('media', []);
            $hasMedia = is_array($media) && count($media) > 0;
            if (! $hasContent && ! $hasMedia) {
                $v->errors()->add('content', 'at least content or media is required');
            }
            if ($hasMedia) {
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
