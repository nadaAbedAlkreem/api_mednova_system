<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationChatRequestRequest extends FormRequest
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
            'patient_id' => 'required|exists:customers,id',
            'consultant_id' => 'required|exists:customers,id',
            'consultant_type'=>'required|in:therapist,rehabilitation_center',
            'consultant_nature'=>'required|in:chat,video',
            'requested_day'=>'required_if:consultant_nature,video|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            'requested_time'=>'required_if:consultant_nature,video|date_format:H:i',
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
           if($this['consultant_nature'] == 'chat')
           {
               $exists =  ConsultationChatRequest::where('patient_id', $this->patient_id)
                   ->where('consultant_id', $this->consultant_id)
                   ->where('status', 'pending')
                   ->exists();

               if ($exists) {

                   $validator->errors()->add('duplicate_request', __('messages.duplicate_request'));
               }
           }elseif($this['consultant_nature'] == 'video')
            {
                $exists = ConsultationVideoRequest::where('patient_id', $this->patient_id)
                    ->where('consultant_id', $this->consultant_id)
                    ->where('appointment_request_id', $this->consultant_id)
                    ->where('status', 'pending')
                    ->exists();

                if ($exists) {

                    $validator->errors()->add('duplicate_request', __('messages.duplicate_request'));
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
            'consultant_type.in' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_type')]),
            'consultant_nature.required' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_nature')]),
            'consultant_nature.in' => __('validation.exists', ['attribute' => __('validation.attributes.consultant_nature')]),

            'requested_day.required' => __('validation.exists', ['attribute' => __('validation.attributes.requested_day')]),
            'requested_day.string' => __('validation.string', ['attribute' => __('validation.attributes.requested_day')]),
            'requested_day.in' => __('validation.in', ['attribute' => __('validation.attributes.requested_day')]),

            'requested_time.required' => __('validation.exists', ['attribute' => __('validation.attributes.requested_time')]),
            'requested_time.date_format' => __('validation.exists', ['attribute' => __('validation.attributes.requested_time')]),

            'type_appointment.required' => __('validation.exists', ['attribute' => __('validation.attributes.type_appointment')]),
            'type_appointment.in' => __('validation.exists', ['attribute' => __('validation.attributes.type_appointment')]),

        ];
    }
    public function getData()
    {
        $data= $this::validated();
        $data['status'] =   $data['status'] ?? 'pending';
        if(isset($data['confirmed_end_time']))
        {
            $data['confirmed_end_time'] = Carbon::parse($data['requested_time'])
                ->addMinutes(60)
                ->format('H:i');
        }



        return $data;


    }
}
