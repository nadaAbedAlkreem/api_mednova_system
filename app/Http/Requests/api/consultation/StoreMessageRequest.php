<?php

namespace App\Http\Requests\api\consultation;

use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
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

            $chat = \App\Models\ConsultationChatRequest::find($this->chat_request_id);

            if (!$chat) {
                return;
            }
            if ($chat->status !== 'active' && $chat->status !== 'accepted') {
                $validator->errors()->add(
                    'status',
                    __('messages.invalid_send')
                );
                return;
            }
            $senderId = (int) auth('api')->id();
            $receiverId = (int) $this->receiver_id;

            $patientId = (int) $chat->patient_id;
            $consultantId = (int) $chat->consultant_id;
            if (!in_array($senderId, [$patientId, $consultantId])) {
                $validator->errors()->add(
                    'sender_id',
                    __('messages.invalid_sender')
                );
                return;
            }

            if ($senderId === $patientId && $receiverId !== $consultantId) {
                $validator->errors()->add(
                    'receiver_id',
                    __('messages.invalid_receiver')
                );
            }

            if ($senderId === $consultantId && $receiverId !== $patientId) {
                $validator->errors()->add(
                    'receiver_id',
                    __('messages.invalid_receiver')
                );
            }
        });
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chat_request_id' => 'required|exists:consultation_chat_requests,id,deleted_at,NULL',
            'receiver_id' => 'required|exists:customers,id,deleted_at,NULL',
            'sender_id' => '',
            'message' => 'nullable|string',
            'attachment' => 'nullable|mimes:pdf,jpg,jpeg,png,gif|max:5120',
            'status' => '',
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
            'chat_request_id.exists'  => __('validation.exists', ['attribute' => __('validation.attributes.chat_request_id')]),
            'receiver_id.required' => __('validation.required', ['attribute' => __('validation.attributes.receiver_id')]),
            'receiver_id.exists'  => __('validation.exists', ['attribute' => __('validation.attributes.receiver_id')]),
            'sender_id.required' => __('validation.required', ['attribute' => __('validation.attributes.sender_id')]),
            'sender_id.exists'  => __('validation.exists', ['attribute' => __('validation.attributes.sender_id')]),
            'message.string'  => __('validation.string', ['attribute' => __('validation.attributes.message')]),
            'attachment.file' => __('validation.file', ['attribute' => __('validation.attributes.receiver_id')]),
            'attachment.max'  => __('validation.max', ['attribute' => __('validation.attributes.receiver_id')]),

        ];
    }
    public function getData()
    {
        $uploadService = new UploadService();
        $data= $this::validated();
        if ($this->hasFile('attachment'))
        {
            $path = $uploadService->upload($this->file('attachment'), 'messages', 'public', 'messages');
            $data['attachment'] =  asset('storage/' . $path);
        }
        $data['sender_id'] =  auth('api')->id();
        return $data;
    }


}
