<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequestRequest extends FormRequest
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
            'service_provider_id' => 'required|exists:customers,id',
            'customer_id' => 'required|exists:customers,id',
            'requested_day' => 'required|string|max:50',
            'requested_time' => 'required|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:pending,approved,rejected,completed',
            'description' => 'nullable|string',
            'confirmed_end_time' => 'nullable|date_format:Y-m-d H:i:s|after:requested_time',
            'session_duration' => 'nullable|integer|min:0',
        ];
    }
    public function messages(): array
    {
        return [
            'service_provider_id.required' => __('validation.required', ['attribute' => __('validation.attributes.service_provider_id')]),
            'service_provider_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.service_provider_id')]),

            'customer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.customer_id')]),

            'requested_day.required' => __('validation.required', ['attribute' => __('validation.attributes.requested_day')]),
            'requested_day.string' => __('validation.string', ['attribute' => __('validation.attributes.requested_day')]),
            'requested_day.max' => __('validation.max.string', ['attribute' => __('validation.attributes.requested_day'), 'max' => 50]),

            'requested_time.required' => __('validation.required', ['attribute' => __('validation.attributes.requested_time')]),
            'requested_time.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.requested_time'), 'format' => 'Y-m-d H:i:s']),

            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),

            'description.string' => __('validation.string', ['attribute' => __('validation.attributes.description')]),

            'confirmed_end_time.date_format' => __('validation.date_format', ['attribute' => __('validation.attributes.confirmed_end_time'), 'format' => 'Y-m-d H:i:s']),
            'confirmed_end_time.after' => __('validation.after', ['attribute' => __('validation.attributes.confirmed_end_time'), 'date' => __('validation.attributes.requested_time')]),

            'session_duration.integer' => __('validation.integer', ['attribute' => __('validation.attributes.session_duration')]),
            'session_duration.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.session_duration'), 'min' => 0]),
        ];
    }
}
