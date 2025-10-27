<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationChatRequest;
use Illuminate\Foundation\Http\FormRequest;

class ChackAvailableSlotsRequest extends FormRequest
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
            'consultant_id' => 'required|integer|exists:customers,id,deleted_at,NULL',
            'consultant_type' => 'required|in:therapist,rehabilitation_center',
            'day' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday', //
            'date' => 'required|date|after_or_equal:today', // التاريخ الفعلي للجلسة 2025-11-27


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
            'consultant_id.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_type.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_type')]),
            'consultant_type.in' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_type')]),
            'day.required' => __('validation.required', ['attribute' => __('validation.attributes.day')]),
            'day.in' => __('validation.in', ['attribute' => __('validation.attributes.day')]),
            'date.required' => __('validation.required', ['attribute' => __('validation.attributes.date')]),
            'date.date' => __('validation.date', ['attribute' => __('validation.attributes.date')]),
            'date.after_or_equal' => __('validation.after_or_equal', ['attribute' => __('validation.attributes.date')]),

        ];
    }

}
