<?php

namespace App\Http\Requests\Api\Financial;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:' . config('financial.withdrawal.min_amount'),
                'max:' . config('financial.withdrawal.max_amount'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ السحب مطلوب.',
            'amount.numeric'  => 'مبلغ السحب يجب أن يكون رقماً.',
            'amount.min'      => 'الحد الأدنى للسحب هو :min ريال عماني.',
            'amount.max'      => 'الحد الأقصى للسحب هو :max ريال عماني.',
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
