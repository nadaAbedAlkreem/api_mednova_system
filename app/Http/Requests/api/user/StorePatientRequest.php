<?php

namespace App\Http\Requests\api\user;

use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\ParameterBag;

class StorePatientRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                'unique:patients,customer_id',
                'exists:customers,id'
            ],
            'gender' => ['required', 'in:Male,Female'],
            'birth_date' => [
                'required',
                'date',
                'before:today',
                'after:1900-01-01'
            ],
            'image' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,svg,webp',
              ],

            'emergency_phone' => ['required', 'regex:/^(\+968\d{8}|\+966\d{9}|\+971\d{9}|\+965\d{8}|\+974\d{8}|\+973\d{8})$/'],
            'relationship' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[\pL\s\-]+$/u'
            ],

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
            'customer_id.integer'  => __('validation.integer', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.exists'   => __('validation.exists', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.unique' => __('validation.unique', ['attribute' => __('validation.attributes.customer_id')]),

            'gender.required' => __('validation.required', ['attribute' => __('validation.attributes.gender')]),
            'gender.in'       => __('validation.in', ['attribute' => __('validation.attributes.gender')]),

            'birth_date.required' => __('validation.required', ['attribute' => __('validation.attributes.birth_date')]),
            'birth_date.date'     => __('validation.date', ['attribute' => __('validation.attributes.birth_date')]),
            'birth_date.before'   => __('validation.before', ['attribute' => __('validation.attributes.birth_date'), 'date' => __('validation.attributes.today')]),
            'birth_date.after'    => __('validation.after', ['attribute' => __('validation.attributes.birth_date'), 'date' => '1900-01-01']),

            'image.required' => __('validation.required', ['attribute' => __('validation.attributes.image')]),
            'image.image'    => __('validation.image', ['attribute' => __('validation.attributes.image')]),
            'image.mimes'    => __('validation.mimes', ['attribute' => __('validation.attributes.image')]),

            'emergency_phone.required' => __('validation.required', ['attribute' => __('validation.attributes.emergency_phone')]),
            'emergency_phone.regex'    => __('validation.regex', ['attribute' => __('validation.attributes.emergency_phone')]),

            'relationship.required' => __('validation.required', ['attribute' => __('validation.attributes.relationship')]),
            'relationship.string'   => __('validation.string', ['attribute' => __('validation.attributes.relationship')]),
            'relationship.min'      => __('validation.min.string', ['attribute' => __('validation.attributes.relationship'), 'min' => 2]),
            'relationship.max'      => __('validation.max.string', ['attribute' => __('validation.attributes.relationship'), 'max' => 50]),
            'relationship.regex'    => __('validation.regex', ['attribute' => __('validation.attributes.relationship')]),
        ];
    }
    public function getData()
    {
        $uploadService = new UploadService();
        $data= $this::validated();
        if ($this->hasFile('image')) {
            $path = $uploadService->upload($this->file('image'), 'patient_profile_images', 'public', 'patientProfile');
            $data['image'] =  asset('storage/' . $path);
        }
        return $data;
    }
}
