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
            'day_of_week' => 'array',
            'day_of_week.*' => 'string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',

            'start_time_morning' => 'date_format:H:i',
            'end_time_morning' => 'date_format:H:i|after:start_time_morning',

            'is_have_evening_time' => 'boolean',
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

        return $data;
    }
}
