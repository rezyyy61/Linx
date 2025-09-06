<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class DonateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supporter_id' => ['sometimes','nullable','integer','exists:campaign_supporters,id'],
            'amount' => ['required','numeric','min:0.5'],
            'currency' => ['sometimes','string','size:3'],
            'provider' => ['sometimes','nullable','string','max:50'],
            'provider_ref' => ['sometimes','nullable','string','max:191'],
            'status' => ['sometimes','in:pending,paid,failed,cancelled'],
            'paid_at' => ['sometimes','nullable','date'],
            'meta' => ['sometimes','nullable','array'],
        ];
    }
}
