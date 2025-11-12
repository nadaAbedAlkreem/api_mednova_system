<?php

namespace App\Http\Requests\api\device;

use App\Models\GloveError;
use Illuminate\Foundation\Http\FormRequest;

class StoreGloveErrorRequest extends FormRequest
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
            'glove_id' => 'required|exists:glove_devices,id,deleted_at,NULL',
            'command_id' => 'nullable|exists:glove_commands,id,deleted_at,NULL',
            'error_type' => 'nullable|string|in:' . implode(',', GloveError::$statusLabels),
            'error_message' => 'required|string|max:500',
        ];
    }
}
