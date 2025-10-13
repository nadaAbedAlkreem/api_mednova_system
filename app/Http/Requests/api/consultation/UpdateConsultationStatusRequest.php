<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationChatRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationStatusRequest extends FormRequest
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
            'id'=>'required|exists:consultation_chat_requests,id',
            'status' => 'required|in:accepted,cancelled,active,completed',
            'action_by' => 'required_if:status,cancelled|in:patient,consultable|nullable',
            'action_reason' => 'required_if:status,cancelled|string|max:500',
        ];
    }
    public function withValidator($validator): void
    {

        $validator->after(function ($validator) {
            $record = ConsultationChatRequest::find($this->id);

            if ($record) {
                if ($record->status === $this->status) {
                    if($this->status == 'accepted')
                    {
                        $validator->errors()->add('status', __('تم قبول الطلب مسبقًا.'));
                    }

                    if($this->status == 'cancelled')
                    {
                        $validator->errors()->add('status', __('تم إلغاء الطلب مسبقًا.'));
                    }

                }
                if(($record->status == 'accepted'|| $record->status ==  'active' || $record->status ==  'completed') && $this->status == 'cancelled' )
                {
                    $validator->errors()->add('status', __('نعتذر منك لا يمكنك ألأن لغي طلب تم اعتماده .'));
                }


            }
        });
    }

    public function getData()
    {
        $data = $this->validated();

        if ($data['status'] === 'cancelled') {
            $data['action_by'] = $data['action_by'] ?? null;
            $data['action_reason'] = $data['action_reason'] ?? null;
        }
        if ($data['status']  === 'accepted') {
            $data['response_at'] = now();
        }
        return $data;
    }


    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
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
            'id.required' => __('validation.required', ['attribute' => __('validation.attributes.id')]),
            'id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.id')]),
            'status.required' => __('validation.required', ['attribute' => __('validation.attributes.status')]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
            'action_by.required' => __('validation.required', ['attribute' => __('validation.attributes.action_by')]),
            'action_by.in' => __('validation.exists', ['attribute' => __('validation.attributes.action_by')]),
            'action_reason.string' => __('validation.string', ['attribute' => __('validation.attributes.action_by')]),
         ];
    }
}
