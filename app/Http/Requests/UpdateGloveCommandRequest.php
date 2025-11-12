<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGloveCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
//            'glove_id' => 'required|exists:glove_devices,id',
//            'command_code' => 'required|string',
//            'status' => 'required|in:success,failed',
//            'error_flag' => 'nullable|integer',
//            'ack_received_at' => 'nullable|date',
        ];
    }
}
