<?php

namespace App\Http\Requests\api\auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'verification_method' => ['required', 'in:email,phone'], // Ensure this is present and valid
            'email' => ['required_if:verification_method,email', 'string', 'email', 'max:255'],
            'phone' => ['required_if:verification_method,phone', 'string'], // Added the second parameter
            'token' => ['required', 'digits:4'], // Ensure it's a 4-digit token
            'password' => ['required', 'string', 'min:8', 'confirmed'], // Ensure password meets requirements
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
        ], 500));
    }
    public function messages()
    {
        return [

            'email.required' => __('messages.email.required'),
            'email.string' => __('messages.email.string'),
            'email.email' => __('messages.email.email'),
            'email.max' => __('messages.email.max'),

            'phone.required' => __('messages.phone.required'),
            'phone.string' => __('messages.phone.string'),
            'phone.prefix' => __('messages.phone.prefix'),

            'token.required' => __('messages.token.required'),
            'token.digits' => __('messages.token.digits'),


            'password.required' => __('messages.password.required'),
            'password.string' => __('messages.password.string'),
            'password.min' => __('messages.password.min'),
            'password.confirmed' => __('messages.password.confirmed'),



        ];
    }



}
