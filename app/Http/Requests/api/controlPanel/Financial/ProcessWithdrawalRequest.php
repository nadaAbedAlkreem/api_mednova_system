<?php

namespace App\Http\Requests\Api\ControlPanel\Financial;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ProcessWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'             => ['required', 'in:approve,reject'],
            'admin_note'         => ['required_if:action,reject', 'nullable', 'string', 'max:1000'],
            'transfer_reference' => ['required_if:action,approve', 'nullable', 'string', 'max:100'],
            'transfer_proof'     => ['required_if:action,approve', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required'                => __('validation.required',    ['attribute' => __('validation.attributes.action')]),
            'action.in'                      => __('validation.in',          ['attribute' => __('validation.attributes.action')]),
            'admin_note.required_if'         => __('validation.required_if', ['attribute' => __('validation.attributes.admin_note'),         'other' => 'action', 'value' => 'reject']),
            'transfer_reference.required_if' => __('validation.required_if', ['attribute' => __('validation.attributes.transfer_reference'), 'other' => 'action', 'value' => 'approve']),
            'transfer_proof.required_if'     => __('validation.required_if', ['attribute' => __('validation.attributes.transfer_proof'),     'other' => 'action', 'value' => 'approve']),
            'transfer_proof.mimes'           => __('validation.mimes',       ['attribute' => __('validation.attributes.transfer_proof'), 'values' => 'jpg, jpeg, png, pdf']),
            'transfer_proof.max'             => __('validation.max.file',    ['attribute' => __('validation.attributes.transfer_proof'), 'max' => 5120]),
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
