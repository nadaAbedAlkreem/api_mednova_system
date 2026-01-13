<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRehabilitationCenterRequest extends FormRequest
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
            'customer_id' => 'required|exists:customers,id,deleted_at,NULL',
            'gender' => '',
            'birth_date' => '',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'full_name' => 'string|max:255',
//            'email' => 'string|email|max:255',
            'phone' => ['string', 'unique:customers,phone,' . $this->customer_id,'regex:/^(\+968\d{8}|\+966\d{9}|\+971\d{9}|\+965\d{8}|\+974\d{8}|\+973\d{8})$/'],

            'specialty_id' => 'array',
            'specialty_id.*' => 'exists:medical_specialties,id,deleted_at,NULL',


            'year_establishment' => 'digits:4|integer|min:1900|max:' . date('Y'),
            'license_number' => 'string|max:100',
            'license_authority' => 'string|max:255',
            'license_file' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bio' => 'string',

            'has_commercial_registration' => 'boolean',
            'commercial_registration_number' => 'required_if:has_commercial_registration,true|string|max:100',
            'commercial_registration_file' => 'required_if:has_commercial_registration,true|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'commercial_registration_authority' => 'required_if:has_commercial_registration,true|string|max:255',

            'video_consultation_price' => [ 'numeric', 'min:0'],
            'chat_consultation_price' => [ 'numeric', 'min:0'],
            'currency' => ['string', 'size:3'],

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

            'gender.in'       => __('validation.in', ['attribute' => __('validation.attributes.gender')]),

            'birth_date.date'     => __('validation.date', ['attribute' => __('validation.attributes.birth_date')]),
            'birth_date.before'   => __('validation.before', ['attribute' => __('validation.attributes.birth_date'), 'date' => __('validation.attributes.today')]),
            'birth_date.after'    => __('validation.after', ['attribute' => __('validation.attributes.birth_date'), 'date' => '1900-01-01']),

            'image.image'    => __('validation.image', ['attribute' => __('validation.attributes.image')]),
            'image.mimes'    => __('validation.mimes', ['attribute' => __('validation.attributes.image')]),

            'full_name.required' => __('validation.required', ['attribute' => __('validation.attributes.full_name')]),
            'full_name.string' => __('validation.string', ['attribute' => __('validation.attributes.full_name')]),
            'full_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.full_name'), 'max' => 255]),

            'email.required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
            'email.string' => __('validation.string', ['attribute' => __('validation.attributes.email')]),
            'email.email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.max' => __('validation.max.string', ['attribute' => __('validation.attributes.email'), 'max' => 255]),
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),

            'phone.required' => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone.string' => __('validation.string', ['attribute' => __('validation.attributes.phone')]),
            'phone.unique' => __('validation.unique', ['attribute' => __('validation.attributes.phone')]),
            'phone.regex' => __('validation.regex', ['attribute' => __('validation.attributes.phone')]),

            'year_establishment.required' => __('validation.required', ['attribute' => __('validation.attributes.year_establishment')]),
            'year_establishment.digits' => __('validation.digits', ['attribute' => __('validation.attributes.year_establishment'), 'digits' => 4]),
            'year_establishment.integer' => __('validation.integer', ['attribute' => __('validation.attributes.year_establishment')]),
            'year_establishment.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.year_establishment'), 'min' => 1900]),
            'year_establishment.max' => __('validation.max.numeric', ['attribute' => __('validation.attributes.year_establishment'), 'max' => date('Y')]),

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

            'has_commercial_registration.required' => __('validation.required', ['attribute' => __('validation.attributes.has_commercial_registration')]),
            'has_commercial_registration.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.has_commercial_registration')]),
            'commercial_registration_number.string' => __('validation.string', ['attribute' => __('validation.attributes.commercial_registration_number')]),
            'commercial_registration_number.max' => __('validation.max.string', ['attribute' => __('validation.attributes.commercial_registration_number'), 'max' => 100]),

            'commercial_registration_file.required' => __('validation.required', ['attribute' => __('validation.attributes.commercial_registration_file')]),
            'commercial_registration_file.file' => __('validation.file', ['attribute' => __('validation.attributes.commercial_registration_file')]),
            'commercial_registration_file.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.commercial_registration_file')]),
            'commercial_registration_file.max' => __('validation.max.file', ['attribute' => __('validation.attributes.commercial_registration_file'), 'max' => 2048]),

            'commercial_registration_authority.required' => __('validation.required', ['attribute' => __('validation.attributes.commercial_registration_authority')]),
            'commercial_registration_authority.string' => __('validation.string', ['attribute' => __('validation.attributes.commercial_registration_authority')]),
            'commercial_registration_authority.max' => __('validation.max.string', ['attribute' => __('validation.attributes.commercial_registration_authority'), 'max' => 255]),

            'day_of_week.required' => __('validation.required', ['attribute' => __('validation.attributes.day_of_week')]),
            'day_of_week.array' => __('validation.array', ['attribute' => __('validation.attributes.day_of_week')]),
            'day_of_week.*.in' => __('validation.in', ['attribute' => __('validation.attributes.day_of_week')]),

            'start_time_morning.required' => __('validation.required', ['attribute' => __('validation.attributes.start_time_morning')]),
            'start_time_morning.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.start_time_morning'), 'format' => 'H:i']),

            'end_time_morning.required' => __('validation.required', ['attribute' => __('validation.attributes.end_time_morning')]),
            'end_time_morning.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.end_time_morning'), 'format' => 'H:i']),
            'end_time_morning.after' => __('validation.after', ['attribute' => __('validation.attributes.end_time_morning'), 'date' => __('validation.attributes.start_time_morning')]),


            'start_time_evening.required' => __('validation.required', ['attribute' => __('validation.attributes.start_time_evening')]),
            'start_time_evening.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.start_time_evening'), 'format' => 'H:i']),

            'end_time_evening.required' => __('validation.required', ['attribute' => __('validation.attributes.end_time_evening')]),
            'end_time_evening.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.end_time_evening'), 'format' => 'H:i']),
            'end_time_evening.after' => __('validation.after', ['attribute' => __('validation.attributes.end_time_evening'), 'date' => __('validation.attributes.start_time_evening')]),

            'is_have_evening_time.required' => __('validation.required', ['attribute' => __('validation.attributes.is_have_evening_time')]),
            'is_have_evening_time.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_have_evening_time')]),

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
    public function getData(): array
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
            $data['license_file'] =  asset('storage/' . $path);;
        }
        $data = collect($data);
        $dataCustomer = $data->only(['customer_id' , 'email' , 'phone','gender', 'birth_date','image']);
        $dataRehabilitation_centers = $data->only(['customer_id','year_establishment' ,'license_number' , 'license_authority' , 'license_file' , 'bio' , 'has_commercial_registration' ,'commercial_registration_number' , 'commercial_registration_file' ,'commercial_registration_authority'  ]);

        return ['customer'=>$dataCustomer  , 'center' => $dataRehabilitation_centers ];
    }

}
