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
    protected function prepareForValidation()
    {
        $this->merge([
            'glove_id' => $this->glove_id == 0 ? null : $this->glove_id,
            'command_id' => $this->command_id == 0 ? null : $this->command_id,
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'glove_id' => 'nullable|exists:glove_devices,id,deleted_at,NULL',
            'command_id' => 'nullable|exists:glove_commands,id,deleted_at,NULL',
            'error_type' => 'nullable|string|in:' . implode(',', GloveError::$statusLabels),
            'error_message' => 'required|string|max:500',
        ];
    }


    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->messages();
        $formattedErrors = [];

        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }

        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => __('messages.ERROR_OCCURRED'),
            'data' => $formattedErrors,
            'status' => 'Internal Server Error'
        ], 422));
    }
}
