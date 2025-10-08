<?php

namespace App\Http\Requests\api\auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => 'required_if:verification_method,email|email',
            'phone' => 'required_if:verification_method,phone|string|max:15',
            'verification_method' => 'required|in:email,phone',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('messages.email.required'),
            'email.email' => __('messages.email.email'),
            'phone.required' => __('messages.phone.required'),
            'verification_method.required' => __('messages.verification_method.required'),
            'verification_method.in' => __('messages.verification_method.in'),
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
        $formattedErrors = ['error' => $errors[0]] ;
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' =>__('messages.ERROR_OCCURRED'),
            'data' => $formattedErrors,
            'status' => 'Internal Server Error'
        ], 500));
    }

}
