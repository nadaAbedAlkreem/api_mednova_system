<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use App\Services\api\TimezoneService;
use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRehabilitationCenterRequest extends FormRequest
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
            'customer_id' => 'required|exists:customers,id,deleted_at,NULL|unique:rehabilitation_centers,customer_id',
            'gender' => 'required',
            'birth_date' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'specialty_id' => 'required|array',
            'specialty_id.*' => 'exists:medical_specialties,id,deleted_at,NULL',
            'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],

            'year_establishment' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'license_number' => 'required|string|max:100',
            'license_authority' => 'required|string|max:255',
            'license_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bio' => 'required|string',

            'has_commercial_registration' => 'required|boolean',
            'commercial_registration_number' => 'required_if:has_commercial_registration,yes|string|max:100',
            'commercial_registration_file' => 'required_if:has_commercial_registration,yes|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'commercial_registration_authority' => 'required_if:has_commercial_registration,yes|string|max:255',

//
//            ///schedule
            'day_of_week' => 'required|array',
            'day_of_week.*' => 'string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            'video_consultation_price' => ['required', 'numeric', 'min:0'],
            'chat_consultation_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],

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
            'type' => '',
            'formatted_address' => 'required',
            'country' => 'required',
            'city' => 'required',


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
        $data = $this::validated();
        if ($this->hasFile('image')) {
            $path = $uploadService->upload($this->file('image'), 'center_profile_images', 'public', 'center_profile');
            $data['image'] = asset('storage/' . $path);
        }

        if ($this->hasFile('certificate_file')) {
            $path = $uploadService->upload($this->file('certificate_file'), 'center_certificate_images', 'public', 'center_profile');
            $data['certificate_file'] = asset('storage/' . $path);
        }

        if ($this->hasFile('commercial_registration_file')) {
            $path = $uploadService->upload($this->file('commercial_registration_file'), 'center_certificate_images', 'public', 'center_profile');
            $data['commercial_registration_file'] = asset('storage/' . $path);
        }

        if ($this->hasFile('license_file')) {
            $path = $uploadService->upload($this->file('license_file'), 'license_certificate_images', 'public', 'centerLicense');
            $data['license_file'] = asset('storage/' . $path);;
        }
        $data = collect($data);
        $dataCustomer = $data->only(['customer_id', 'gender', 'birth_date', 'image']);
        $dataLocation = $data->only(['customer_id', 'formatted_address', 'city', 'country']);
        $dataRehabilitation_centers = $data->only(['customer_id', 'year_establishment', 'license_number', 'license_authority', 'license_file', 'bio', 'has_commercial_registration', 'commercial_registration_number', 'commercial_registration_file', 'commercial_registration_authority']);
        $data['consultant_id'] = $data['customer_id'];
        $data['consultant_type'] = 'rehabilitation_center';
        $data['day_of_week'] = json_encode($data['day_of_week']);
        $data['type'] = 'online';
        $customer = Customer::find($data['customer_id']);
        if ($customer) {
            $localTimezone = $customer->timezone ?? config('app.timezone');
            $data['start_time_morning'] = TimezoneService::toUTCHour($data['start_time_morning'], $localTimezone);
            $data['end_time_morning'] = TimezoneService::toUTCHour($data['end_time_morning'], $localTimezone);
            if ($data['is_have_evening_time']) {
                $data['start_time_evening'] = TimezoneService::toUTCHour($data['start_time_evening'], $localTimezone);
                $data['end_time_evening'] = TimezoneService::toUTCHour($data['end_time_evening'], $localTimezone);
            }

        }
        $dataSchedule = $data->only(['type', 'consultant_id', 'consultant_type', 'day_of_week', 'start_time_morning', 'end_time_morning', 'start_time_evening', 'end_time_evening', 'is_have_evening_time']);

        return ['customer' => $dataCustomer, 'location' => $dataLocation, 'schedule' => $dataSchedule, 'center' => $dataRehabilitation_centers];
    }


}
