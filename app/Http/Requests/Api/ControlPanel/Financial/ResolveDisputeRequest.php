<?php

namespace App\Http\Requests\Api\ControlPanel\Financial;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', 'in:refund,release'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution.required' => 'نوع القرار مطلوب.',
            'resolution.in'       => 'القرار يجب أن يكون refund أو release.',
        ];
    }
}
