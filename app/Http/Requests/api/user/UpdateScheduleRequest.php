<?php

namespace App\Http\Requests\api\user;

use App\Services\Api\Customer\TimezoneService;
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
            'schedule_id' => 'required|exists:schedules,id,deleted_at,NULL',
            'day_of_week' => 'array',
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
            'schedule_id.required' => __('validation.required', ['attribute' => __('validation.attributes.schedule_id')]),
            'schedule_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.schedule_id')]),
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
        if(isset($data['is_have_evening_time']) && $data['is_have_evening_time'] == 0)
        {
            $data['start_time_evening'] = null ;
            $data['end_time_evening'] = null ;

        }
        $customer = auth()->user();
        if ($customer) {
           $localTimezone = $customer->timezone ?? config('app.timezone');
            if(isset($data['start_time_morning'])) {
                $data['start_time_morning'] = TimezoneService::toUTCHour($data['start_time_morning'], $localTimezone);
            }
            if(isset($data['end_time_morning'])) {
                $data['end_time_morning'] = TimezoneService::toUTCHour($data['end_time_morning'], $localTimezone);
            }
            if(isset($data['is_have_evening_time']) && $data['is_have_evening_time'] == 1)
            {
                if(isset($data['start_time_evening'])) {
                    $data['start_time_evening'] = TimezoneService::toUTCHour($data['start_time_evening'], $localTimezone);
                }
                if(isset($data['end_time_evening'])) {
                    $data['end_time_evening'] = TimezoneService::toUTCHour($data['end_time_evening'], $localTimezone);
                }
            }
        }
        return $data;
    }
}
