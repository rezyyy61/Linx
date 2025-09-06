<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class FollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes','integer','exists:users,id'],
            'role' => ['sometimes','in:follower,volunteer,donor'],
            'contact_email' => ['sometimes','nullable','email','max:190'],
            'contact_phone' => ['sometimes','nullable','string','max:40'],
            'tags_json' => ['sometimes','nullable','array'],
        ];
    }
}
