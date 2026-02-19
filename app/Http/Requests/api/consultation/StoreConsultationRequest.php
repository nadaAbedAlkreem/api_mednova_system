<?php

namespace App\Http\Requests\api\consultation;

use App\Models\AppointmentRequest;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Services\Api\Customer\TimezoneService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    protected $timeZone ;

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
            'patient_id' => 'required|exists:customers,id,deleted_at,NULL',
            'consultant_id' => 'required|exists:customers,id,deleted_at,NULL',
            'consultant_type'=>'required|in:therapist,rehabilitation_center',
            'consultant_nature'=>'required|in:chat,video',
            'requested_day'=>'required_if:consultant_nature,video|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            'requested_time'=>'required_if:consultant_nature,video|date_format:Y-m-d H:i',
            'timezone' => ['required_if:consultant_nature,video',Rule::in(\DateTimeZone::listIdentifiers())],
            'type_appointment'=>'required_if:consultant_nature,video|in:online,offline',
            'confirmed_end_time'=>'',



        ];
    }



    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
             $patient = \App\Models\Customer::find($this->patient_id);
            $consultant = \App\Models\Customer::find($this->consultant_id);

            if ($patient && $patient->type_account !== 'patient') {
                $validator->errors()->add('patient_id',  __('messages.patient_account'));
            }
            if ($consultant && $consultant->type_account !== $this->consultant_type) {

                $validator->errors()->add('consultant_id', __('messages.consultant_account'));
            }
            $patient = \App\Models\Customer::find($this->patient_id);
            $patientTimezone =  $this->timezone  ?? $patient->timezone ;
            // تحويل وقت البدء إلى UTC
            $requestedTimeUtc = TimezoneService::toUTC($this['requested_time'], $patientTimezone);
            $statuses = [
                'pending'  => __('messages.consultation_pending'),
                'accepted' => __('messages.consultation_accepted'),
                'active'   => __('messages.consultation_active'),
            ];

            if($this['consultant_nature'] == 'chat')
               {
                   $exists =  ConsultationChatRequest::where('patient_id', $this->patient_id)
                       ->where('consultant_id', $this->consultant_id)
                       ->whereIn('status', array_keys($statuses))
                       ->first();
                   if ($exists) {
                       $validator->errors()->add('duplicate_request', $statuses[$exists->status]);
                   }
               }elseif($this['consultant_nature'] == 'video')
                {
                    $exists = ConsultationVideoRequest::where('patient_id', $this->patient_id)
                        ->where('consultant_id', $this->consultant_id)
                        ->whereHas('appointmentRequest',function($query) use ($requestedTimeUtc) {
                            $query->where('requested_time', $requestedTimeUtc->format('Y-m-d H:i'));
                        })
                        ->whereIn('status', array_keys($statuses))
                        ->first();
                    if ($exists) {

                        $validator->errors()->add('duplicate_request', $statuses[$exists->status]);
                    }


                    // وقت الآن حسب منطقة المريض
                    $nowPatientTime = Carbon::now($patientTimezone);

                    try {
                        $requestedAt = Carbon::parse($this['requested_time']);
                    } catch (\Exception $e) {
                        $validator->errors()->add(
                            'requested_time',
                            'صيغة التاريخ/الوقت غير صحيحة.'
                        );
                        return;
                    }
                    // ❌ منع حجز تاريخ سابق
                    if ($requestedAt->lt($nowPatientTime)) {
                        $validator->errors()->add(
                            'requested_time',
                            'لا يمكن حجز موعد بتاريخ سابق. الرجاء اختيار وقت لاحق.'
                        );
                    }

                    // التحقق أن اليوم يطابق التاريخ
                    $actualDay = $requestedAt->format('l');
                    if ($actualDay !== $this['requested_day']) {
                        $validator->errors()->add(
                            'requested_day',
                            "اليوم المدخل ({$this['requested_day']}) لا يطابق اليوم الفعلي لتاريخ الاستشارة ($actualDay)."
                        );
                    }

                }

        });
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
            'patient_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.patient_id')]),
            'consultant_id.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_id')]),
            'consultant_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_id')]),

            'consultant_type.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_type')]),
            'consultant_type.in' => __('validation.in', ['attribute' => __('validation.attributes.consultant_type')]),

            'consultant_nature.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_nature')]),
            'consultant_nature.in' => __('validation.in', ['attribute' => __('validation.attributes.consultant_nature')]),

            'requested_day.required' => __('validation.required', ['attribute' => __('validation.attributes.requested_day')]),
            'requested_day.string' => __('validation.string', ['attribute' => __('validation.attributes.requested_day')]),
            'requested_day.in' => __('validation.in', ['attribute' => __('validation.attributes.requested_day')]),

            'requested_time.required' => __('validation.required', ['attribute' => __('validation.attributes.requested_time')]),
            'requested_time.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.requested_time')]),

            'type_appointment.required' => __('validation.required', ['attribute' => __('validation.attributes.type_appointment')]),
            'type_appointment.in' => __('validation.in', ['attribute' => __('validation.attributes.type_appointment')]),
            'timezone.required' => __('validation.required', ['attribute' => __('validation.attributes.timezone')]),

        ];
    }
    public function getData()
    {
        $data= $this::validated();
        $data['status'] =   $data['status'] ?? 'pending';
         if (isset($data['requested_time'])) {
//             $patient = \App\Models\Customer::find($this->patient_id);
//             $patientTimezone = $patient->timezone ;
             // تحويل وقت البدء إلى UTC
             $requestedTimeUtc = TimezoneService::toUTC($this['requested_time'], $this['timezone']);
             $data['requested_time'] = $requestedTimeUtc;
             // حساب وقت الانتهاء (بناءً على مدة 60 دقيقة) وتحويله أيضاً إلى UTC
             $confirmedEndTimeUtc = Carbon::parse($requestedTimeUtc)->addMinutes(60)->format('Y-m-d H:i:s');
             $data['confirmed_end_time'] = $confirmedEndTimeUtc;

        }
         return $data;


    }
}
