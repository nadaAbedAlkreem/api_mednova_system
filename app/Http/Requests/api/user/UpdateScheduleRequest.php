<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
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
            'schedule_id' => 'required|exists:schedules,id',
            'day_of_week' => 'required|array',
            'day_of_week.*' => 'string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',

            'start_time_morning' => 'required|date_format:H:i',
            'end_time_morning' => 'required|date_format:H:i|after:start_time_morning',

            'is_have_evening_time' => 'required|boolean',
            'start_time_evening' => 'required_if:is_have_evening_time,true|date_format:H:i',
            'end_time_evening' => 'required_if:is_have_evening_time,true|date_format:H:i|after:start_time_evening',

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
        ];
    }
    public function getData()
    {
        $data= $this::validated();
         return $data;
    }
}
