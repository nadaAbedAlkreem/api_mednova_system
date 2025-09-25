<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderNotificationRequest extends FormRequest
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
                'channel' => 'required|in:sms,email,whatsapp,push',
                'message' => 'required|string|max:1000',
                'send_type' => 'required|in:relative,absolute',
                'send_after_minutes' => 'nullable|required_if:send_type,relative|integer|min:1',
                'send_at' => 'nullable|required_if:send_type,absolute|date|after:now',
                'trigger_event' => 'nullable|in:register_created,order_created,order_pending,order_accepted,order_rejected,manual||required_if:send_type,relative',
                'status' => 'nullable|in:pending,sent,failed',
                'sent_at' => 'nullable|date',
        ];
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
        ], 500));
    }
}
