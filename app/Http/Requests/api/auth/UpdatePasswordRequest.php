<?php

namespace App\Http\Requests\Api\Auth;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordRequest extends FormRequest
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
            'current_password' => 'required|min:8',
            'new_password' => 'required|min:8',
        ];
    }
    public function messages()
    {
        return [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'current_password.min' => 'يجب أن تكون كلمة المرور الحالية 8 أحرف على الأقل.',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'new_password.min' => 'يجب أن تكون كلمة المرور الجديدة 8 أحرف على الأقل.',
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
    public function getData($user)
    {
        $data = $this->validated();
        if (!$user) {
            throw new Exception('not found user ', 401);
        }
        if ($user && Hash::check($data['current_password'], $user->password))
        {
            throw new Exception('كلمة المرور الحالية لا تطابق الموجودة ,حاول مرة اخرى', 401);

        }
        $data['new_password'] = Hash::make($data['new_password']);

        return $data;
    }


}
