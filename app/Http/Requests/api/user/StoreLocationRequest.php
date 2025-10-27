<?php

namespace App\Http\Requests\api\user;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
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
            'customer_id' => 'required|unique:locations,customer_id,NULL,id,deleted_at,NULL|exists:customers,id,deleted_at,NULL',
            'latitude' => '',
            'longitude' => '',
            'formatted_address'=>'',
            'region' => '',
            'country' => '',
            'city' => '',
            'district' => '',
            'postal_code' => '',
            'location_type' => '',
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

            'latitude.required' => __('validation.required', ['attribute' => __('validation.attributes.latitude')]),
            'longitude.required' => __('validation.required', ['attribute' => __('validation.attributes.longitude')]),
            'formatted_address.required' => __('validation.required', ['attribute' => __('validation.attributes.formatted_address')]),
            'city.required' => __('validation.required', ['attribute' => __('validation.attributes.city')]),
            'country.required' => __('validation.required', ['attribute' => __('validation.attributes.country')]),
            'location_type.required' => __('validation.required', ['attribute' => __('validation.attributes.location_type')]),
        ];
    }

}
