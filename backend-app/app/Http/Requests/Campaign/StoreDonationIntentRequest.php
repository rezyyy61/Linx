<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // عمومی
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
