<?php

namespace App\Http\Requests\api\user;

use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreTherapistRequest extends FormRequest
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
            'gender' => 'required',
            'birth_date' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'customer_id' => 'required|exists:customers,id|unique:therapists,customer_id',
            'medical_specialties_id' => 'required|exists:medical_specialties,id',
            'experience_years' => 'required|integer|min:0|max:80',
            'university_name' => 'required|string|max:255',
            'countries_certified' => 'required|string',
            'graduation_year' => 'required|digits:4|integer|min:1950|max:' . date('Y'),
            'certificate_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'license_number' => 'required|string|max:100',
            'license_authority' => 'required|string|max:255',
            'license_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bio' => 'required|string',
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

            'medical_specialties_id.required' => __('validation.required', ['attribute' => __('validation.attributes.medical_specialties_id')]),
            'medical_specialties_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.medical_specialties_id')]),

            'experience_years.required' => __('validation.required', ['attribute' => __('validation.attributes.experience_years')]),
            'experience_years.integer' => __('validation.integer', ['attribute' => __('validation.attributes.experience_years')]),
            'experience_years.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.experience_years'), 'min' => 0]),
            'experience_years.max' => __('validation.max.numeric', ['attribute' => __('validation.attributes.experience_years'), 'max' => 80]),

            'university_name.required' => __('validation.required', ['attribute' => __('validation.attributes.university_name')]),
            'university_name.string' => __('validation.string', ['attribute' => __('validation.attributes.university_name')]),
            'university_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.university_name'), 'max' => 255]),

            'countries_certified.required' => __('validation.required', ['attribute' => __('validation.attributes.countries_certified')]),
            'countries_certified.string' => __('validation.string', ['attribute' => __('validation.attributes.countries_certified')]),

            'graduation_year.required' => __('validation.required', ['attribute' => __('validation.attributes.graduation_year')]),
            'graduation_year.digits' => __('validation.digits', ['attribute' => __('validation.attributes.graduation_year'), 'digits' => 4]),
            'graduation_year.integer' => __('validation.integer', ['attribute' => __('validation.attributes.graduation_year')]),
            'graduation_year.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.graduation_year'), 'min' => 1950]),
            'graduation_year.max' => __('validation.max.numeric', ['attribute' => __('validation.attributes.graduation_year'), 'max' => date('Y')]),

            'certificate_file.required' => __('validation.required', ['attribute' => __('validation.attributes.certificate_file')]),
            'certificate_file.file' => __('validation.file', ['attribute' => __('validation.attributes.certificate_file')]),
            'certificate_file.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.certificate_file')]),
            'certificate_file.max' => __('validation.max.file', ['attribute' => __('validation.attributes.certificate_file'), 'max' => 2048]),

            'license_number.required' => __('validation.required', ['attribute' => __('validation.attributes.license_number')]),
            'license_number.string' => __('validation.string', ['attribute' => __('validation.attributes.license_number')]),
            'license_number.max' => __('validation.max.string', ['attribute' => __('validation.attributes.license_number'), 'max' => 100]),

            'license_authority.required' => __('validation.required', ['attribute' => __('validation.attributes.license_authority')]),
            'license_authority.string' => __('validation.string', ['attribute' => __('validation.attributes.license_authority')]),
            'license_authority.max' => __('validation.max.string', ['attribute' => __('validation.attributes.license_authority'), 'max' => 255]),

            'license_file.required' => __('validation.required', ['attribute' => __('validation.attributes.license_file')]),
            'license_file.file' => __('validation.file', ['attribute' => __('validation.attributes.license_file')]),
            'license_file.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.license_file')]),
            'license_file.max' => __('validation.max.file', ['attribute' => __('validation.attributes.license_file'), 'max' => 2048]),

            'bio.required' => __('validation.required', ['attribute' => __('validation.attributes.bio')]),
            'bio.string' => __('validation.string', ['attribute' => __('validation.attributes.bio')]),
        ];
    }


    public function getData()
    {
        $uploadService = new UploadService();
        $data= $this::validated();
        if ($this->hasFile('image')) {
            $path = $uploadService->upload($this->file('image'), 'therapist_profile_images' ,'public' ,'therapist_profile');
            $data['image'] =  asset('storage/' . $path);
        }
        if ($this->hasFile('certificate_file')) {
            $path = $uploadService->upload($this->file('certificate_file'), 'therapist_certificate_images' ,'public' ,'therapistCertificate');
            $data['certificate_file'] =  asset('storage/' . $path);
        }
        if ($this->hasFile('license_file')) {
            $path = $uploadService->upload($this->file('license_file'), 'license_certificate_images','public', 'therapistLicense');
            $data['license_file'] =  asset('storage/' . $path);
        }
        return $data;
    }
}
