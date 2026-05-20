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
            'amount.required' => __('validation.required', ['attribute' => __('validation.attributes.amount')]),
            'amount.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.amount')]),
            'amount.min' => __('validation.min', ['attribute' => __('validation.attributes.amount')]),
            'amount.max' => __('validation.max', ['attribute' => __('validation.attributes.amount')]),
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
            'data' => $formattedErrors,
            'status' => 'Internal Server Error',
        ], 422));
    }
}
