<?php

namespace App\Http\Requests\api\payment;

use Illuminate\Foundation\Http\FormRequest;

class WalletTopUpRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:card,apple_pay,bank'],
            'card_token' => ['required_if:payment_method,card'],
            'bank_account_id' => ['required_if:payment_method,bank'],
        ];
    }
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        $formattedErrors = [];

        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }

        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => __('messages.ERROR_OCCURRED'),
            'data' => $formattedErrors,
            'status' => 'Internal Server Error'
        ], 422, [], JSON_UNESCAPED_UNICODE));
    }

    public function messages(): array
    {
        return [
            // amount
            'amount.required' => __('validation.required', ['attribute' => __('validation.attributes.amount'),]),
            'amount.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.amount'),]),
            'amount.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.amount'), 'min' => 1,]),
            // payment_method
            'payment_method.required' => __('validation.required', ['attribute' => __('validation.attributes.payment_method'),]),
            'payment_method.in' => __('validation.in', ['attribute' => __('validation.attributes.payment_method'),]),
            // card_token
            'card_token.required_if' => __('validation.required_if', ['attribute' => __('validation.attributes.card_token'), 'other' => __('validation.attributes.payment_method'), 'value' => __('validation.values.payment_method.card'),]),
            // bank_account_id
            'bank_account_id.required_if' => __('validation.required_if', ['attribute' => __('validation.attributes.bank_account_id'), 'other' => __('validation.attributes.payment_method'), 'value' => __('validation.values.payment_method.bank'),]),
        ];
    }




}
