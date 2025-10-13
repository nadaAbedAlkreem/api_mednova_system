<?php

namespace App\Http\Requests\api\program;

use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',  // يجب أن يكون موجودًا في جدول العملاء
            'title_ar' => 'nullable|string|max:255',         // العنوان العربي مطلوب
//            'title_en' => 'nullable|string|max:255',         // العنوان الانجليزي اختياري
            'description_ar' => 'nullable|string',
//            'description_en' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240', // اختياري، حجم أقصى 10MB
            'price' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => __('validation.required', ['attribute' => __('validation.attributes.program_id')]),
            'program_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.program_id')]),

            'title_ar.required' => __('validation.required', ['attribute' => __('validation.attributes.title_ar')]),
            'title_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.title_ar')]),
            'title_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title_ar'), 'max' => 255]),

            'title_en.string' => __('validation.string', ['attribute' => __('validation.attributes.title_en')]),
            'title_en.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title_en'), 'max' => 255]),

            'description_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.description_ar')]),
            'description_en.string' => __('validation.string', ['attribute' => __('validation.attributes.description_en')]),

            'cover_image.image' => __('validation.image', ['attribute' => __('validation.attributes.cover_image')]),
            'cover_image.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.cover_image'), 'values' => 'jpeg,png,jpg,gif,svg']),
            'cover_image.max' => __('validation.max.file', ['attribute' => __('validation.attributes.cover_image'), 'max' => 10240]),

            'price.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.price')]),
            'price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.price'), 'min' => 0]),
        ];
    }
    public function getData()
    {
        $uploadService = new UploadService();
        $data= $this::validated();
        if ($this->hasFile('cover_image')) {
            $path = $uploadService->upload($this->file('cover_image'), 'program_images', 'public', 'programs');
            $data['cover_image'] =  asset('storage/' . $path);
        }
        return $data;
    }


}
