<?php

namespace App\Http\Requests\Comment;

use App\Rules\Comment\ValidCommentable;
use Illuminate\Foundation\Http\FormRequest;

class ListRootCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string'],
            'commentable_id' => ['required', new ValidCommentable],
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'in:new,top'],
        ];
    }
}
