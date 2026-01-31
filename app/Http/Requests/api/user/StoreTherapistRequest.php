<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use App\Services\Api\Customer\TimezoneService;
use App\Services\Api\Customer\UploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'customer_id' => 'required|exists:customers,id,deleted_at,NULL|unique:therapists,customer_id',
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
            'timezone' => ['required',  Rule::in(\DateTimeZone::listIdentifiers())],
            //location
            'formatted_address'=>'required',
            'country' => 'required',
            'city' => 'required',
            ///schedule
            'day_of_week' => 'required|array',
            'day_of_week.*' => 'string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',

             'start_time_morning' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $hour = intval(explode(':', $value)[0]);
                    if ($hour >= 12) {
                        $fail('الوقت الصباحي يجب أن يكون قبل الساعة 12:00.');
                    }
                }
            ],
            'end_time_morning' => [
                'required',
                'date_format:H:i',
                'after:start_time_morning',
                function ($attribute, $value, $fail) {
                    $hour = intval(explode(':', $value)[0]);
                    if ($hour >= 12) {
                        $fail('نهاية الفترة الصباحية يجب أن تكون قبل الساعة 12:00.');
                    }
                }
            ],
            'is_have_evening_time' => 'required|boolean',
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
            'video_consultation_price' => ['required', 'numeric', 'min:0'],
            'chat_consultation_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
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

            'formatted_address.required' => __('validation.required', ['attribute' => __('validation.attributes.formatted_address')]),
            'city.required' => __('validation.required', ['attribute' => __('validation.attributes.city')]),
            'country.required' => __('validation.required', ['attribute' => __('validation.attributes.country')]),
            'timezone.required' => __('validation.required', ['attribute' => __('validation.attributes.timezone')]),

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
        $data = collect($data);
        $data['consultant_id'] =  $data['customer_id'] ;
        $data['consultant_type'] = 'therapist' ;
        $data['day_of_week'] = json_encode($data['day_of_week'] ) ;
        $data['type'] = 'online' ;
        ////

        $customer = Customer::find($data['customer_id']) ;
        if($customer)
        {
            $localTimezone = $customer->timezone ?? config('app.timezone');
            $data['start_time_morning'] = TimezoneService::toUTCHour($data['start_time_morning'], $localTimezone);
            $data['end_time_morning'] = TimezoneService::toUTCHour($data['end_time_morning'], $localTimezone);

         if($data['is_have_evening_time'])
         {
             $data['start_time_evening'] = TimezoneService::toUTCHour($data['start_time_evening'], $localTimezone);
             $data['end_time_evening'] = TimezoneService::toUTCHour($data['end_time_evening'], $localTimezone);
         }

        }

         $dataSchedule = $data->only(['consultant_id' , 'consultant_type' , 'day_of_week','type' , 'start_time_morning' , 'end_time_morning' , 'start_time_evening' , 'end_time_evening', 'is_have_evening_time' ]);
         return ['data'=>$data ,'schedule'=> $dataSchedule] ;
    }
}
