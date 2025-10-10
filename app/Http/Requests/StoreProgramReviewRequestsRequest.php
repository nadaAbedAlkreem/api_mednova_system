<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramReviewRequestsRequest extends FormRequest
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
            'program_id' => 'required|exists:programs,id',
            'requested_by' => 'required|exists:customers,id',

        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
        $formattedErrors = ['error' => $errors[0]] ;
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
            'requested_by.required' => __('validation.required', ['attribute' => __('validation.attributes.requested_by')]),
            'requested_by.exists' => __('validation.exists', ['attribute' => __('validation.attributes.requested_by')]),
            'program_id.required' => __('validation.required', ['attribute' => __('validation.attributes.program_id')]),
            'program_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.program_id')]),
         ];
       }

}
