<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'member_id' => ['nullable', 'exists:users,id'],
            'contact_info' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
