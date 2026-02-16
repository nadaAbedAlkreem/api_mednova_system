<?php

namespace App\Http\Requests\api\user;

use App\Services\Api\Customer\UploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateTherapistRequest extends FormRequest
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
            'gender' => 'in:Male,Female',
            'birth_date' => 'date|after_or_equal:1950-01-01|before_or_equal:today',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'customer_id' => 'required|exists:customers,id,deleted_at,NULL',
            'full_name' => 'string|max:255',
            'email' => 'string|email|max:255',
            'phone' => ['string','unique:customers,phone,'.$this->customer_id, 'regex:/^(\+968\d{8}|\+966\d{9}|\+971\d{9}|\+965\d{8}|\+974\d{8}|\+973\d{8})$/'],
            'medical_specialties_id' => 'exists:medical_specialties,id,deleted_at,NULL',
            'experience_years' => 'integer|min:0|max:50',
            'university_name' => 'string|max:255',
            'countries_certified' => 'string',
            'graduation_year' => 'digits:4|integer|min:1950|max:' . date('Y'),
            'certificate_file' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'license_number' => 'string|max:100',
            'license_authority' => 'string|max:255',
            'license_file' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bio' => ' string',
            'video_consultation_price' => ['numeric', 'min:0' ,'regex:/^\d{1,12}(\.\d{1,3})?$/'],
            'chat_consultation_price' => ['numeric', 'min:0' ,'regex:/^\d{1,12}(\.\d{1,3})?$/'],
            'currency' => ['string', 'size:3'],
            'timezone' => [Rule::in(\DateTimeZone::listIdentifiers())],
            'day_of_week' => 'array',
            'day_of_week.*' => 'string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',

            'start_time_morning' => [
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $hour = intval(explode(':', $value)[0]);
                    if ($hour >= 12) {
                        $fail('الوقت الصباحي يجب أن يكون قبل الساعة 12:00.');
                    }
                }
            ],
            'end_time_morning' => [
                'date_format:H:i',
                'after:start_time_morning',
                function ($attribute, $value, $fail) {
                    $hour = intval(explode(':', $value)[0]);
                    if ($hour >= 12) {
                        $fail('نهاية الفترة الصباحية يجب أن تكون قبل الساعة 12:00.');
                    }
                }
            ],
            'is_have_evening_time' => 'boolean',
            'start_time_evening' => [
                'required_if:is_have_evening_time,true',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $hour = intval(explode(':', $value)[0]);
                    if ($hour < 12) {
                        $fail('الوقت المسائي يجب أن يكون بعد الساعة 12:00.');
                    }
                }
            ],
            'end_time_evening' => [
                'required_if:is_have_evening_time,true',
                'date_format:H:i',
                'after:start_time_evening',
                function ($attribute, $value, $fail) {
                    $hour = intval(explode(':', $value)[0]);
                    if ($hour < 12) {
                        $fail('نهاية الفترة المسائية يجب أن تكون بعد الساعة 12:00.');
                    }
                }
            ],
            'formatted_address' => '',
            'country' => '',
            'city' => '',
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

            'birth_date.required' => __('validation.required', ['attribute' => __('validation.attributes.birth_date')]),
            'birth_date.before_or_equal' => __('validation.before_or_equal', ['attribute' => __('validation.attributes.birth_date')]),
            'birth_date.after_or_equal' => __('validation.after_or_equal', ['attribute' => __('validation.attributes.birth_date')]),

            'customer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.unique' => __('validation.unique', ['attribute' => __('validation.attributes.customer_id')]),
            'video_consultation_price.regex' => __('validation.regex', ['attribute' => __('validation.attributes.video_consultation_price')]),
            'chat_consultation_price.regex' => __('validation.regex', ['attribute' => __('validation.attributes.chat_consultation_price')]),

            'full_name.required' => __('validation.required', ['attribute' => __('validation.attributes.full_name')]),
            'full_name.string' => __('validation.string', ['attribute' => __('validation.attributes.full_name')]),
            'full_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.full_name'), 'max' => 255]),
            'gender.in'       => __('validation.in', ['attribute' => __('validation.attributes.gender')]),

            'birth_date.date'     => __('validation.date', ['attribute' => __('validation.attributes.birth_date')]),
            'birth_date.before'   => __('validation.before', ['attribute' => __('validation.attributes.birth_date'), 'date' => __('validation.attributes.today')]),
            'birth_date.after'    => __('validation.after', ['attribute' => __('validation.attributes.birth_date'), 'date' => '1900-01-01']),

            'image.image'    => __('validation.image', ['attribute' => __('validation.attributes.image')]),
            'image.mimes'    => __('validation.mimes', ['attribute' => __('validation.attributes.image')]),

            'email.required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
            'email.string' => __('validation.string', ['attribute' => __('validation.attributes.email')]),
            'email.email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.max' => __('validation.max.string', ['attribute' => __('validation.attributes.email'), 'max' => 255]),
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),

            'phone.required' => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone.string' => __('validation.string', ['attribute' => __('validation.attributes.phone')]),
            'phone.unique' => __('validation.unique', ['attribute' => __('validation.attributes.phone')]),
            'phone.regex' => __('validation.regex', ['attribute' => __('validation.attributes.phone')]),

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

            'video_consultation_price.required' => __('validation.required', ['attribute' => __('validation.attributes.video_consultation_price')]),
            'video_consultation_price.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.video_consultation_price')]),
            'video_consultation_price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.video_consultation_price')]),
            'chat_consultation_price.required' => __('validation.required', ['attribute' => __('validation.attributes.chat_consultation_price')]),
            'chat_consultation_price.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.chat_consultation_price')]),
            'chat_consultation_price.min' =>  __('validation.min.numeric', ['attribute' => __('validation.attributes.chat_consultation_price')]),
            'currency.required' => __('validation.required', ['attribute' => __('validation.attributes.currency')]),
            'currency.string' =>__('validation.string', ['attribute' => __('validation.attributes.currency')]),
            'currency.size' =>__('validation.size.string', ['attribute' => __('validation.attributes.currency')]),
        ];
    }


//    public function getData()
//    {
//        $uploadService = new UploadService();
//        $data= $this::validated();
//
//        if ($this->hasFile('image')) {
//            $path = $uploadService->upload($this->file('image'), 'therapist_profile_images' ,'public' ,'therapist_profile');
//            $data['image'] =  asset('storage/' . $path);
//        }
//
//        if ($this->hasFile('certificate_file')) {
//            $path = $uploadService->upload($this->file('certificate_file'), 'therapist_certificate_images' ,'public' ,'therapistCertificate');
//            $data['certificate_file'] =  asset('storage/' . $path);
//        }
//
//        if ($this->hasFile('license_file')) {
//            $path = $uploadService->upload($this->file('license_file'), 'license_certificate_images','public', 'therapistLicense');
//            $data['license_file'] =  asset('storage/' . $path);;
//        }
//        return $data;
//    }
}
