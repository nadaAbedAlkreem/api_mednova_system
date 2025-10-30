<?php

namespace App\Http\Requests\api\consultation;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class ChackAvailableSlotsRequest extends FormRequest
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

    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {

            // 1) تحقق أن التاريخ قابل للـ parse
            try {
                $date = Carbon::parse($this->input('date'));
            } catch (\Throwable $e) {
                $validator->errors()->add('date', 'التاريخ غير صالح.');
                return;
            }

            // 2) تحقق تطابق day مع التاريخ (Carbon->format('l') يرجع اسم اليوم بالإنجليزية)
            $sentDay = $this->input('day'); // متوقع مثلاً "Monday"
            if ($date->format('l') !== $sentDay) {
                $validator->errors()->add('day', 'اليوم لا يتوافق مع التاريخ المرسل.');
                // لا نرجع فوراً — قد تريد جمع أكثر من خطأ، لكن يمكنك return إذا رغبت
                return;
            }

            // 3) تحقق أن هذا المختص لديه جدول فعال وأن هذا اليوم مسموح
            $consultantId = $this->input('consultant_id');
            $consultantType = $this->input('consultant_type');

            $schedule = Schedule::where('consultant_id', $consultantId)
                ->where('is_active', true)
                ->where('consultant_type', $consultantType)
                ->first();
             if (! $schedule) {
                $validator->errors()->add('consultant_id', 'لا يوجد جدول مواعيد لهذا المختص.');
                return;
            }

            $daysOfWeek = $schedule->day_of_week; // نوعه JSON أو TEXT
            if (is_string($daysOfWeek)) {
                $daysOfWeek = json_decode($daysOfWeek, true); // حوّله لمصفوفة إذا كان JSON
            }

            if (!is_array($daysOfWeek) || !in_array($this['day'], $daysOfWeek)) {
                $validator->errors()->add('day', 'اليوم المختار غير متاح في جدول هذا المختص.');
                return;
            }

        });
    }
    public function rules(): array
    {
         return [
             'patient_id' => 'required|integer|exists:customers,id,deleted_at,NULL',
             'consultant_id' => 'required|integer|exists:customers,id,deleted_at,NULL',
             'consultant_type' => 'required|in:therapist,rehabilitation_center',
             'day' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday', // مفروض هذه القيمة يتم تحدديها من قبل الفروتت حسب ابام كل مستشار
             'date' => 'required|date|after_or_equal:today', // التاريخ الفعلي للجلسة 2025-11-27
             'type_appointment' => 'required|string|in:offline,online',
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
            'patient_id.required' => __('validation.required', ['attribute' => __('validation.attributes.patient_id')]),
            'patient_id.integer' => __('validation.integer', ['attribute' => __('validation.attributes.patient_id')]),
            'patient_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.patient_id')]),

            'consultant_id.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_id.integer' => __('validation.integer', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_type.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_type')]),
            'consultant_type.in' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_type')]),
            'day.required' => __('validation.required', ['attribute' => __('validation.attributes.day')]),
            'day.in' => __('validation.in', ['attribute' => __('validation.attributes.day')]),
            'date.required' => __('validation.required', ['attribute' => __('validation.attributes.date')]),
            'date.date' => __('validation.date', ['attribute' => __('validation.attributes.date')]),
            'type_appointment.required' => __('validation.required', ['attribute' => __('validation.attributes.type_appointment')]), //
            'type_appointment.in' => __('validation.in', ['attribute' => __('validation.attributes.type_appointment')]), //
            'type_appointment.string' => __('validation.string', ['attribute' => __('validation.attributes.type_appointment')]), //


        ];
    }

}
