<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationStatusRequest extends FormRequest
{
    protected $table;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
         $nature = $this->input('consultant_nature');

         $this->table = match ($nature) {
            'video' => 'consultation_video_requests',
            'chat' => 'consultation_chat_requests',
            default => null,
        };
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id'=>'required|exists:'.$this->table.',id,deleted_at,NULL',
            'status' => 'required|in:accepted,cancelled,active,completed',
            'consultant_nature' => 'required|in:video,chat' ,
            'action_by' => 'required_if:status,cancelled|in:patient,consultable|nullable',
            'action_reason' => 'required_if:status,cancelled|string|max:500', ];
    }

    public function withValidator($validator): void
    {

        $validator->after(function ($validator) {

            $record = ($this->consultant_nature == 'video')?  ConsultationVideoRequest::find($this->id) :  ConsultationChatRequest::find($this->id);

            if ($record) {
               if($this->consultant_nature == 'chat')
               {
                   if ($record->patient_message_count > 0 && $record->consultant_message_count > 0) {
                       if ($this->status == 'cancelled') {
                           $validator->errors()->add('status', __('لا يمكنك لغي جلسة ألأن .'));
                       }
                   }
               }

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
                if(($record->status == 'cancelled') && $this->status == 'accepted' )
                {
                    $validator->errors()->add('status', __('نعتذر منك لا يمكنك ألأن اعتماد طلب تم لغيه .'));
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
            'id.required' => __('validation.required', ['attribute' => __('validation.attributes.id_con')]),
            'id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.id_con')]),
            'status.required' => __('validation.required', ['attribute' => __('validation.attributes.status')]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
            'action_by.required' => __('validation.required', ['attribute' => __('validation.attributes.action_by')]),
            'action_by.in' => __('validation.exists', ['attribute' => __('validation.attributes.action_by')]),
            'action_reason.string' => __('validation.string', ['attribute' => __('validation.attributes.action_by')]),
            'consultant_nature.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_nature')]),
            'consultant_nature.in' => __('validation.in', ['attribute' => __('validation.attributes.consultant_nature')]),



        ];
    }
}
