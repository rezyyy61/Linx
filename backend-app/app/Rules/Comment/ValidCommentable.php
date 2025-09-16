<?php

namespace App\Rules\Comment;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class ValidCommentable implements ValidationRule
{
    public function __construct(private string $typeField = 'commentable_type') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $type = request()->input($this->typeField);

        $allowed = config('comments.allowed_types');
        if (! is_array($allowed) || empty($allowed)) {
            $map = config('comments.types', []);
            $allowed = is_array($map) ? array_values($map) : [];
        } else {
            $allowed = array_values($allowed);
        }

        if (! in_array($type, $allowed, true)) {
            $fail('invalid_commentable_type');

            return;
        }

        if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
            $fail('invalid_commentable_type');

            return;
        }

        if (! $type::query()->whereKey($value)->exists()) {
            $fail('invalid_commentable_id');
        }
    }
}
