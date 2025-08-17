<?php

namespace App\Http\Requests\Media;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;

class CreatePresignedUrlRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'extension' => 'required|string|max:10',
            'type' => 'required|in:'.implode(',', array_column(MediaType::cases(), 'value')),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
