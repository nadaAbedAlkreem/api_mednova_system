<?php

namespace App\Http\Requests\api\user;

use App\Services\Api\Customer\UploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
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
        $nature = $this->input('consultant_nature');
        $table = match ($nature) {
            'video' => 'consultation_video_requests',
            'chat' => 'consultation_chat_requests',
            default => null,
        };

        $idRules = ['nullable'];
        if ($table) {
            $idRules[] = "exists:{$table},id,deleted_at,NULL";
        }
        $categories = [
            'consultations',
            'payments',
            'courses',
            'hardware',
            'system',
            'other',
        ];

        // Subcategory ENUM (لكل الفئات)
        $subcategories = [
            // Consultations
            'session_not_done',
            'consultant_absent',
            'patient_absent',
            'wrong_medical_info',
            'misconduct',
            'technical_issue',

            // Payments
            'payment_not_received',
            'wrong_amount_deducted',
            'payment_failed',
            'delayed_transfer',
            'refund_request',

            // Courses
            'course_not_as_described',
            'low_quality_content',
            'missing_updates',
            'access_issue',

            // Hardware
            'device_not_received',
            'device_damaged',
            'missing_items',
            'warranty_issue',
            'replacement_request',

            // System
            'login_issue',
            'bug_issue',
            'suspicious_activity',
            'abuse_report',

            // Other
            'other',
        ];



        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'customer_id' => ['required', 'integer', 'exists:customers,id,deleted_at,NULL'],
            'reported_customers_id' => ['nullable', 'integer', 'exists:customers,id,deleted_at,NULL'],
            'consultation_id' => $idRules,
            'consultant_nature' => 'required_with:consultation_id|in:video,chat',
            'category' => ['required', Rule::in($categories)],
            'subcategory' => [Rule::requiredIf(fn() => $this->category !== 'other'), Rule::in($subcategories), 'nullable'],
            'custom_category' => 'required_if:consultation_id,other',
            'custom_subcategory' => 'required_if:consultation_id,other',
            'type' => ['required', Rule::in(['abuse', 'payment', 'technical', 'other'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'attachments' => ['nullable', 'array', 'max:5'], // أقصى 5 ملفات
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt', 'max:5120'], // كل ملف حتى 5 ميجا
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:25'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'title.string' => __('validation.string', ['attribute' => __('validation.attributes.title')]),
            'title.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title'), 'max' => 255]),
            'description.required' => __('validation.required', ['attribute' => __('validation.attributes.description')]),
            'description.min' => __('validation.min.string', ['attribute' => __('validation.attributes.description'), 'min' => 10]),
            'description.max' => __('validation.max.string', ['attribute' => __('validation.attributes.description'), 'max' => 5000]),
            'customer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.customer_id')]),
            'reported_customers_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.reported_customers_id')]),
            'consultation_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultation_id')]),
            'type.required' => __('validation.required', ['attribute' => __('validation.attributes.type')]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'priority.in' => __('validation.in', ['attribute' => __('validation.attributes.priority')]),
            'attachments.array' => __('validation.array', ['attribute' => __('validation.attributes.attachments')]),
            'attachments.max' => __('validation.max.array', ['attribute' => __('validation.attributes.attachments'), 'max' => 5]),
            'attachments.*.file' => __('validation.file', ['attribute' => __('validation.attributes.attachments')]),
            'attachments.*.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.attachments')]),
            'attachments.*.max' => __('validation.max.file', ['attribute' => __('validation.attributes.attachments'), 'max' => 5120]),
            'contact_email.email' => __('validation.email', ['attribute' => __('validation.attributes.contact_email')]),
            'contact_phone.max' => __('validation.max.string', ['attribute' => __('validation.attributes.contact_phone'), 'max' => 25]),

            'category.required' => __('validation.required', ['attribute' => __('validation.attributes.category')]),
            'category.in' => __('validation.in', ['attribute' => __('validation.attributes.category')]),

            'subcategory.required' => __('validation.required', ['attribute' => __('validation.attributes.subcategory')]),
            'subcategory.in' => __('validation.in', ['attribute' => __('validation.attributes.subcategory')]),

            'custom_category.required_if' => __('validation.required', ['attribute' => __('validation.attributes.custom_category')]),
            'custom_category.in' => __('validation.required', ['attribute' => __('validation.attributes.custom_category')]),

            'custom_subcategory.required_if' => __('validation.required', ['attribute' => __('validation.attributes.custom_subcategory')]),
            'custom_subcategory.in' => __('validation.in', ['attribute' => __('validation.attributes.custom_subcategory')]),


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
    public function getData()
    {
        $uploadService = new UploadService();
        $data = $this::validated();
        if ($this->hasFile('attachments')) {
            $path = $uploadService->upload($this->file('attachments'), 'report', 'public', 'attachments');
            $data['attachments'] = asset('public/storage/' . $path);
        }
        return $data;
    }

}
