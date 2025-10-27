<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $consultant = Customer::find($this->consultant_id);
            if ($consultant && $consultant->type_account !== $this->consultant_type ) {
                $validator->errors()->add('consultant_type', 'قيمة نوع الحساب مع بيانات مستشار غير مطابقة ');
            }


        });
    }
    public function rules(): array
    {
        return [
            'consultant_id' => 'required|exists:customers,id,deleted_at,NULL',
            'consultant_type' => 'required|in:therapist,rehabilitation_center',

            'day_of_week' => 'required|array',
            'day_of_week.*' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',

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
            'type' => 'required|in:online,offline',

        ];
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            'consultant_id.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_id')]),

            'consultant_type.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_type')]),
            'consultant_type.in' => __('validation.in', ['attribute' => __('validation.attributes.consultant_type')]),

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

            'type.required' => __('validation.required', ['attribute' => __('validation.attributes.type_time')]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type_time')]),

        ];
    }
    public  function  getData()
    {
        $data = $this->validated();
        $data['day_of_week'] = json_encode($data['day_of_week']) ;
        return $data;

    }
}
