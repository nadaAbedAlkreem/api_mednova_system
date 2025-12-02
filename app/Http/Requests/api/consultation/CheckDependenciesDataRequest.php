<?php

namespace App\Http\Requests\api\consultation;

use App\Models\ConsultationVideoRequest;
use Illuminate\Foundation\Http\FormRequest;

class CheckDependenciesDataRequest extends FormRequest
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
            'consultation_id' => 'required|integer|exists:consultation_video_requests,id,deleted_at,NULL',
            'customer_id' => 'required|integer|exists:customers,id,deleted_at,NULL',
            'customer_type' => 'required|in:patient,therapist,rehabilitation_center',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $consultation = ConsultationVideoRequest::find($this->consultation_id);

            if (!$consultation) {
                $validator->errors()->add('consultation_id', __('validation.exists', ['attribute' => __('validation.attributes.consultation_id')]));
                return;
            }
            if($this->type_account == 'patient')
            {
               if($consultation->patient_id != $this->customer_id)
               {
                       $validator->errors()->add('customer_id', __('validation.custom.patient_not_linked'));
                   return;
               }
            }else if ( $this->type_account == 'therapist'  ||  $this->type_account == 'rehabilitation_center')
            {
                if($consultation->consultant_id != $this->customer_id)
                {
                    $validator->errors()->add('customer_id', __('validation.custom.consultant_not_linked'));
                    return;
                }
            }


        });
   }


    public function messages(): array
    {

        return [
            'consultation_id.required' => __('validation.required', ['attribute' => __('validation.attributes.consultation_id')]),
            'consultation_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultation_id')]),
            'customer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.consultation_id')]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.consultation_id')]),
            'type_account.required' => __('validation.required', ['attribute' => __('validation.attributes.type_account')]),
            'type_account.in' => __('validation.in', ['attribute' => __('validation.attributes.type_account')]),
        ];
    }
}
