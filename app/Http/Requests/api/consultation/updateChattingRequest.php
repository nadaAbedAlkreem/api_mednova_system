<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationChatRequest;
use Illuminate\Foundation\Http\FormRequest;

class updateChattingRequest extends FormRequest
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
            'chat_request_id' => 'required|exists:consultation_chat_requests,id',
            'first_patient_message_at' => 'nullable|date',
            'first_consultant_message_at' => 'nullable|date',
            'patient_message_count' => 'nullable|integer',
            'consultant_message_count' => 'nullable|integer',
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
    public function messages(): array
    {

        return [
            'chat_request_id.required' => __('validation.required', ['attribute' => __('validation.attributes.chat_request_id')]),
            'chat_request_id.exists'  => __('validation.exists', ['attribute' => __('validation.attributes.chat_request_id')]),
            'first_patient_message_at.date' => __('validation.date', ['attribute' => __('validation.attributes.first_patient_message_at')]),
            'first_consultant_message_at.date' => __('validation.date', ['attribute' => __('validation.attributes.first_consultant_message_at')]),

            'patient_message_count.integer' => __('validation.integer', ['attribute' => __('validation.attributes.patient_message_count')]),
            'consultant_message_count.integer' => __('validation.integer', ['attribute' => __('validation.attributes.consultant_message_count')]),


        ];
    }

}
