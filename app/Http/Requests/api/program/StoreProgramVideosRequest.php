<?php

namespace App\Http\Requests\api\program;

use App\Services\api\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgramVideosRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',  // يجب أن يكون موجودًا في جدول العملاء
            'videos' => 'nullable|array',
            'videos.*.title_ar' => 'required|string|max:255',
//            'videos.*.title_en' => 'nullable|string|max:255',
            'videos.*.description_ar' => 'required|string|max:255',
//            'videos.*.description_en' => 'nullable|string|max:255',
            'videos.*.video_path' => 'required|file|mimes:mp4,mov,avi|max:512000', // أقصى 500MB
            'videos.*.duration_minute' => 'nullable|integer|min:0',
            'videos.*.order' => 'nullable|integer|min:0',
            'videos.*.is_preview' => 'nullable|boolean',
            'videos.*.is_free' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.required' => __('validation.required', ['attribute' => __('validation.attributes.program_id')]),
            'program_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.program_id')]),

            // الفيديوهات
            'videos.array' => __('validation.array', ['attribute' => __('validation.attributes.videos')]),

            'videos.*.title_ar.required' => __('validation.required', ['attribute' => __('validation.attributes.video_title_ar')]),
            'videos.*.title_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.video_title_ar')]),
            'videos.*.title_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.video_title_ar'), 'max' => 255]),

            'videos.*.video_file.required' => __('validation.required', ['attribute' => __('validation.attributes.video_file')]),
            'videos.*.video_file.file' => __('validation.file', ['attribute' => __('validation.attributes.video_file')]),
            'videos.*.video_file.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.video_file'), 'values' => 'mp4,mov,avi']),
            'videos.*.video_file.max' => __('validation.max.file', ['attribute' => __('validation.attributes.video_file'), 'max' => 512000]),

            'videos.*.duration_minute.integer' => __('validation.integer', ['attribute' => __('validation.attributes.duration_minute')]),
            'videos.*.duration_minute.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.duration_minute'), 'min' => 0]),

            'videos.*.order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.order')]),
            'videos.*.order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.order'), 'min' => 0]),

            'videos.*.is_preview.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_preview')]),
            'videos.*.is_free.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_free')]),
        ];
    }
    public function getData()
    {
        $uploadService = new UploadService();
        $data= $this::validated();
        if (isset($data['videos']) && is_array($data['videos'])) {
            foreach ($data['videos'] as $index => $video) {
                if ($this->hasFile("videos.$index.video_path")) {
                    $path = $uploadService->upload(
                        $this->file("videos.$index.video_path"),
                        'program_video',
                        'public',
                        'videos'
                    );
                    $data['videos'][$index]['video_path'] = asset('public/storage/' . $path);
                }
            }
        }
        return $data;
    }


}
