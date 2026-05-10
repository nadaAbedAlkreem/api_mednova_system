<?php

namespace App\Http\Requests\Api\Consultation;

use App\Enums\ConsultationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisputeRequest extends FormRequest
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
            'type' => ['required', Rule::in([ConsultationType::CHAT->value,ConsultationType::VIDEO->value])],
            'reason_dispute' => ['required', 'string', 'max:1000'],
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
            'type.required' => __('validation.required', [
                'attribute' => __('validation.attributes.consultation_type')
            ]),

            'type.in' => __('validation.in', [
                'attribute' => __('validation.attributes.consultation_type')
            ]),
            'reason_dispute.required' => __('validation.required', [
                'attribute' => __('validation.attributes.reason_dispute')
            ]),
            'reason_dispute.string' => __('validation.string', [
                'attribute' => __('validation.attributes.reason_dispute')
            ]),

            'reason_dispute.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.reason_dispute'),
                'max' => 1000
            ]),
        ];
    }
}
