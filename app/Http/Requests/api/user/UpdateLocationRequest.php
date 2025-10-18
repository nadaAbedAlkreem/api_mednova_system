<?php

namespace App\Http\Requests\api\user;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
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
            'customer_id' => 'required|exists:customers,id',
            'latitude'          => 'numeric|',
            'longitude'         => 'numeric|',
            'formatted_address' => 'string',
            'country'           => 'string|max:255',
            'region'            => 'string|max:255',
            'city'              => 'string|max:255',
            'district'          => 'string|max:255',
            'postal_code'       => 'string|max:20',
            'location_type'     => 'string|max:50',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->messages();
        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }
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
            'customer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.unique' => __('validation.unique', ['attribute' => __('validation.attributes.customer_id')]),
        ];
    }

}
