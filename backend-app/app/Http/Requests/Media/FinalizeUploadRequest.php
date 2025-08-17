<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
