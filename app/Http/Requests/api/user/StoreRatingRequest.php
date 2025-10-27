<?php

namespace App\Http\Requests\api\user;

use App\Models\Customer;
use App\Models\Program;
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
            if ($reviewee && $reviewee->type_account == 'patient') {
                $validator->errors()->add('reviewer_id', 'المقيم يجب أن يكون يا مختص يا مركز.');
            }
            $type = $this->input('reviewee_type');
            $id = $this->input('reviewee_id');

            switch ($type) {
                case 'customer':
                    if (!\App\Models\Customer::where('id', $id)->exists()) {
                        $validator->errors()->add('reviewee_id', 'المستخدم غير موجود.');
                    }
                    break;

                case 'program':
                    if (!\App\Models\Program::where('id', $id)->exists()) {
                        $validator->errors()->add('reviewee_id', 'البرنامج غير موجود.');
                    }
                    break;

//                case 'platform':
//                    if (!\App\Models\Platform::where('id', $id)->exists()) {
//                        $validator->errors()->add('reviewee_id', 'المنصة غير موجودة.');
//                    }
                    break;
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
            'reviewer_id'  => 'required|exists:customers,id,deleted_at,NULL',
            'reviewee_type' => 'required|string|in:customer,program,platform',
            'reviewee_id'   => 'required',
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
    public function handle()
    {
        $data = $this->validated();
        $data['reviewee_type'] = ($data['reviewee_type']) == 'customer' ? 'App\\Models\\Customer' :'App\\Models\\Program';
        return $data;
     }
}
