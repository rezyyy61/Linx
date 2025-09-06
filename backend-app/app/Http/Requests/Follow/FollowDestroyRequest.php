<?php

namespace App\Http\Requests\Follow;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class FollowDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        if (! $actor || ! $target instanceof User) {
            return false;
        }

        return $actor->can('unfollow', $target);
    }

    public function rules(): array
    {
        return [
            'confirm' => ['required', 'accepted'],
        ];
    }
}
