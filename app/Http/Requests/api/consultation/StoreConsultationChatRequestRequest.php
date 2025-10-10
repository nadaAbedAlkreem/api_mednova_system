<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationChatRequest;
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

        ];
    }



    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $patient = \App\Models\Customer::find($this->patient_id);
            $consultant = \App\Models\Customer::find($this->consultant_id);
            if ($patient && $patient->type_account !== 'patient') {
                $validator->errors()->add('patient_id', __('معرف المريض المحدد لا ينتمي إلى حساب مريض.'));
            }
            if ($consultant && $consultant->type_account !== $this->consultant_type) {
                $validator->errors()->add('consultant_id', __('معرف المستشار المحدد لا يتطابق مع نوع المستشار.'));
            }
            $exists =  ConsultationChatRequest::where('patient_id', $this->patient_id)
                ->where('consultant_id', $this->consultant_id)
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                $validator->errors()->add('duplicate_request', __('لقد قمت بإرسال هذا الطلب مسبقًا، يرجى انتظار القبول.'));
            }
        });
    }


    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
        $formattedErrors = ['error' => $errors[0]] ;
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
        ];
    }
}
