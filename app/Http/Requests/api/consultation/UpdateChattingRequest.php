<?php

namespace App\Http\Requests\api\consultation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChattingRequest extends FormRequest
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
            'chat_request_id' => [
                'required',
                Rule::exists('consultation_chat_requests', 'id')->whereIn('status', ['accepted', 'active']),
            ],
            'first_patient_message_at' => 'nullable|date',
            'first_consultant_message_at' => 'nullable|date',
            'patient_message_count' => 'integer|min:1|required_without:consultant_message_count',
            'consultant_message_count' => 'integer|min:1|required_without:patient_message_count',
            'status'=> '',
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
            'chat_request_id.required' => __('validation.required', ['attribute' => __('validation.attributes.chat_request_id')]),
            'chat_request_id.exists'  => 'يمكن تحديث المحادثة فقط إذا كانت موجودة و  حالة الاستشارة مقبولة او نشطة .',

            'first_patient_message_at.date' => __('validation.date', ['attribute' => __('validation.attributes.first_patient_message_at')]),
            'first_consultant_message_at.date' => __('validation.date', ['attribute' => __('validation.attributes.first_consultant_message_at')]),

            'patient_message_count.integer' => __('validation.integer', ['attribute' => __('validation.attributes.patient_message_count')]),
            'consultant_message_count.integer' => __('validation.integer', ['attribute' => __('validation.attributes.consultant_message_count')]),

            'patient_message_count.min' => __('validation.min', ['attribute' => __('validation.attributes.patient_message_count')]),
            'consultant_message_count.min' => __('validation.min', ['attribute' => __('validation.attributes.consultant_message_count')]),

            'patient_message_count.required_without' => __('validation.required_without', ['attribute' => __('validation.attributes.patient_message_count')]),
            'consultant_message_count.required_without' => __('validation.required_without', ['attribute' => __('validation.attributes.consultant_message_count')]),

        ];
    }
    public function getData()
    {
        $data = $this->validated();
        if (
            (!is_null($data['first_patient_message_at']) || !is_null($data['first_consultant_message_at']))
            && $this->consultation->status === 'accepted' // فقط إذا كانت مقبولة
        ) {

            dd($this->consultation);
            $data['status'] = 'active';
            $data['started_at'] = now();
            $eventType  = '';

            // تحديد نص الرسالة حسب من بدأ التفاعل
            if (!is_null($data['first_patient_message_at'])) {
                $notificationMessage = "أصبحت جلسة استشارة بينك وبين الدكتور أحمد، اذهب الآن للاستفادة من الجلسة.";
                $eventType = 'active_by_patient';
            } elseif (!is_null($data['first_consultant_message_at'])) {
                $notificationMessage = "أرسل الدكتور أحمد أول رسالة في جلسة الاستشارة، اذهب الآن للرد عليه.";
                $eventType = 'active_by_consultant';

            }
            // إطلاق الحدث مع الرسالة المناسبة
            event(new \App\Events\ConsultationRequested(
                $this->consultation,
                $notificationMessage,
                $eventType,
            ));
        }
        return $data;
    }

}
