<?php

namespace App\Http\Requests\Api\Financial;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name'           => ['required', 'string', 'max:100'],
            'account_holder_name' => ['required', 'string', 'max:100'],
            'account_number'      => ['required', 'string', 'min:8', 'max:30'],
            'iban'                => ['nullable', 'string', 'max:34'],
            'swift_code'          => ['nullable', 'string', 'max:11'],
            'bank_country'        => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required'           => 'اسم البنك مطلوب.',
            'bank_name.string'             => 'اسم البنك يجب أن يكون نصاً.',
            'bank_name.max'                => 'اسم البنك لا يجب أن يتجاوز 100 حرف.',
            'account_holder_name.required' => 'اسم صاحب الحساب مطلوب.',
            'account_holder_name.string'   => 'اسم صاحب الحساب يجب أن يكون نصاً.',
            'account_holder_name.max'      => 'اسم صاحب الحساب لا يجب أن يتجاوز 100 حرف.',
            'account_number.required'      => 'رقم الحساب مطلوب.',
            'account_number.string'        => 'رقم الحساب يجب أن يكون نصاً.',
            'account_number.min'           => 'رقم الحساب يجب أن يكون 8 أحرف على الأقل.',
            'account_number.max'           => 'رقم الحساب لا يجب أن يتجاوز 30 حرفاً.',
            'iban.string'                  => 'رقم الآيبان يجب أن يكون نصاً.',
            'iban.max'                     => 'رقم الآيبان لا يجب أن يتجاوز 34 حرفاً.',
            'swift_code.string'            => 'رمز السويفت يجب أن يكون نصاً.',
            'swift_code.max'               => 'رمز السويفت لا يجب أن يتجاوز 11 حرفاً.',
            'bank_country.string'          => 'رمز الدولة يجب أن يكون نصاً.',
            'bank_country.size'            => 'رمز الدولة يجب أن يتكون من حرفين (مثال: OM، SA).',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->messages();
        $formattedErrors = [];

        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }

        throw new ValidationException($validator, response()->json([
            'success' => false,
            'message' => __('messages.ERROR_OCCURRED'),
            'data'    => $formattedErrors,
            'status'  => 'Internal Server Error',
        ], 422));
    }
}
