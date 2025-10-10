<?php

namespace App\Http\Requests\api\auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
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
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,NULL,id,deleted_at,NULL',
            'phone' => ['required', 'string', 'unique:customers,phone,NULL,id,deleted_at,NULL', 'regex:/^(\+968\d{8}|\+966\d{9}|\+971\d{9}|\+965\d{8}|\+974\d{8}|\+973\d{8})$/'],
            'password' => ['required', 'string', 'min:8' ,'confirmed'],
            'type_account' => ['required', 'string' , 'in:therapist,rehabilitation_center,patient'],
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

            'full_name.required' => __('validation.required', ['attribute' => __('validation.attributes.full_name')]),
            'full_name.string' => __('validation.string', ['attribute' => __('validation.attributes.full_name')]),
            'full_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.full_name'), 'max' => 255]),

            'email.required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
            'email.string' => __('validation.string', ['attribute' => __('validation.attributes.email')]),
            'email.email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.max' => __('validation.max.string', ['attribute' => __('validation.attributes.email'), 'max' => 255]),
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),

            'phone.required' => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone.string' => __('validation.string', ['attribute' => __('validation.attributes.phone')]),
            'phone.unique' => __('validation.unique', ['attribute' => __('validation.attributes.phone')]),
            'phone.regex' => __('validation.regex', ['attribute' => __('validation.attributes.phone')]),

            'password.required' => __('validation.required', ['attribute' => __('validation.attributes.password')]),
            'password.string' => __('validation.string', ['attribute' => __('validation.attributes.password')]),
            'password.min' => __('validation.min.string', ['attribute' => __('validation.attributes.password'), 'min' => 8]),
            'password.confirmed' => __('validation.confirmed', ['attribute' => __('validation.attributes.password')]),

            'type_account.required' => __('validation.required', ['attribute' => __('validation.attributes.type_account')]),
            'type_account.string' => __('validation.string', ['attribute' => __('validation.attributes.type_account')]),
            'type_account.in' => __('validation.string', ['attribute' => __('validation.attributes.type_account')]),

        ];
    }


    public function getData()
    {
        $data= $this::validated();
        if(isset($data['password'])){
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }




}
