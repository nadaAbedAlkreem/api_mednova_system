<?php

namespace App\Http\Requests\Api\Consultation;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Policies\ConsultationPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationStatusRequest extends FormRequest
{
    protected $table;
    protected string $denyReason = '';

    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize(): bool
    {
        $id     = $this->input('id');
        $nature = $this->input('consultant_nature');

        if (!$id || !$nature) {
            $this->denyReason = __('messages.MISSING_CONSULTATION_DATA');
            return false;
        }

        $consultation = $this->fetchConsultation($nature, $id);

        if (!$consultation) {
            return true;
        }

        $user   = $this->user();
        $policy = new ConsultationPolicy();

        if (!$policy->updateStatus($user, $consultation)) {
            $this->denyReason = __('policies.consultation.update_status.not_owner');
            return false;
        }

        if ($this->input('status') === 'accepted') {
            $result = $policy->accept($user, $consultation);
            if ($result->denied()) {
                $this->denyReason = $result->message();
                return false;
            }
            return true;
        }

        if ($this->input('status') === 'cancelled') {
            $actionBy = $this->input('action_by');
            if ($actionBy && !$policy->cancelAs($user, $consultation, $actionBy)) {
                $this->denyReason = __('policies.consultation.cancel.wrong_role');
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nature = $this->input('consultant_nature');
        $table  = match ($nature) {
            'video' => 'consultation_video_requests',
            'chat'  => 'consultation_chat_requests',
            default => null,
        };

        $idRules = ['required'];
        if ($table) {
            $idRules[] = "exists:{$table},id,deleted_at,NULL";
        }

        return [
            'id'                 => $idRules,
            'status'             => 'required|in:accepted,cancelled,active,completed',
            'consultant_nature'  => 'required|in:video,chat',
            'action_by'          => 'required_if:status,cancelled|nullable|in:patient,consultable',
            'action_reason'      => 'required_if:status,cancelled|nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $nature = $this->input('consultant_nature');
            $record = $this->fetchConsultation($nature, $this->input('id'));

            if (!$record) {
                return;
            }

            // لا يمكن إلغاء جلسة شات بدأت فعلاً
            if ($nature === 'chat' && $this->input('status') === 'cancelled') {
                if ($record->patient_message_count > 0 && $record->consultant_message_count > 0) {
                    $validator->errors()->add('status', __('لا يمكنك إلغاء جلسة بدأت فعلاً.'));
                }
            }

            // الحالة نفسها مرة ثانية
            if ($record->status === $this->input('status')) {
                $validator->errors()->add('status', match ($this->input('status')) {
                    'accepted'  => __('تم قبول الطلب مسبقًا.'),
                    'cancelled' => __('تم إلغاء الطلب مسبقًا.'),
                    default     => __('الحالة نفسها موجودة مسبقًا.'),
                });
            }

            // لا يمكن قبول طلب ملغى
            if ($record->status === 'cancelled' && $this->input('status') === 'accepted') {
                $validator->errors()->add('status', __('لا يمكنك قبول طلب تم إلغاؤه.'));
            }
        });
    }

    public function getData(): array
    {
        $data = $this->validated();

        if ($data['status'] === 'cancelled') {
            $data['action_by']     = $data['action_by']     ?? null;
            $data['action_reason'] = $data['action_reason'] ?? null;
        }

        if ($data['status'] === 'accepted') {
            $data['response_at'] = now();
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function fetchConsultation(
        string $nature,
        mixed $id
    ): ConsultationChatRequest|ConsultationVideoRequest|null {
        if (!$id || !$nature) {
            return null;
        }

        return match ($nature) {
            'video' => ConsultationVideoRequest::find($id),
            'chat'  => ConsultationChatRequest::find($id),
            default => null,
        };
    }

    // -------------------------------------------------------------------------
    // Error Formatting
    // -------------------------------------------------------------------------


    protected function failedAuthorization(): never
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $this->denyReason ?: __('messages.UNAUTHORIZED_CONSULTATION_ACTION'),
                'data'    => [],
                'status'  => 'Forbidden',
            ], 403)
        );
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): never
    {
        $errors = collect($validator->errors()->messages())
            ->map(fn($messages) => $messages[0])
            ->toArray();

        throw new \Illuminate\Validation\ValidationException(
            $validator,
            response()->json([
                'success' => false,
                'message' => __('messages.ERROR_OCCURRED'),
                'data'    => $errors,
                'status'  => 'Unprocessable Entity',
            ], 422)
        );
    }

    public function messages(): array
    {
        return [
            'id.required'                => __('validation.required', ['attribute' => __('validation.attributes.id_con')]),
            'id.exists'                  => __('validation.exists',   ['attribute' => __('validation.attributes.id_con')]),
            'status.required'            => __('validation.required', ['attribute' => __('validation.attributes.status')]),
            'status.in'                  => __('validation.in',       ['attribute' => __('validation.attributes.status')]),
            'action_by.required_if'      => __('validation.required', ['attribute' => __('validation.attributes.action_by')]),
            'action_by.in'               => __('validation.in',       ['attribute' => __('validation.attributes.action_by')]),
            'action_reason.required_if'  => __('validation.required', ['attribute' => __('validation.attributes.action_reason')]),
            'action_reason.string'       => __('validation.string',   ['attribute' => __('validation.attributes.action_reason')]),
            'consultant_nature.required' => __('validation.required', ['attribute' => __('validation.attributes.consultant_nature')]),
            'consultant_nature.in'       => __('validation.in',       ['attribute' => __('validation.attributes.consultant_nature')]),
        ];
    }

}
