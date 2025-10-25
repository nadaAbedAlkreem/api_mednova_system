<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
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
            $reviewer = Customer::find($this->reviewer_id);
            $reviewee = Customer::find($this->reviewee_id);

            if ($reviewer && $reviewer->type_account !== 'patient') {
                $validator->errors()->add('reviewer_id', 'المراجع يجب أن يكون مريضًا.');
            }

            if ($reviewee &&(( $reviewee->type_account == 'therapist' && $this['reviewee_type'] != 'therapist' ) || ($reviewee->type_account == 'rehabilitation_center' && $this['reviewee_type'] != 'rehabilitation_center' ))  ) { //rehabilitation_center
                $validator->errors()->add('reviewee_id', 'المقيَّم يجب أن يكون مختصًا أو مركزًا.');
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
            'reviewer_id'  => 'required|exists:customers,id',
            'reviewee_id'   => 'required|exists:customers,id',
            'reviewee_type' =>'required|in:therapist,rehabilitation_center',
            'rating'  => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string'
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
            'reviewer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.reviewer_id')]),
            'reviewee_id.required'    => __('validation.required', ['attribute' => __('validation.attributes.reviewee_id')]),
            'reviewer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.reviewer_id')]),
            'reviewee_id.exists'    => __('validation.exists', ['attribute' => __('validation.attributes.reviewee_id')]),
            'reviewee_type.required'    => __('validation.required', ['attribute' => __('validation.attributes.reviewee_type')]),
            'reviewee_type.in'    => __('validation.in', ['attribute' => __('validation.attributes.reviewee_type')]),
            'rating.decimal'    => __('validation.decimal', ['attribute' => __('validation.attributes.rating')]),
            'rating.required'    => __('validation.required', ['attribute' => __('validation.attributes.rating')]),

            'comment.string'    => __('validation.string', ['attribute' => __('validation.attributes.comment')]),
        ];
    }
}
