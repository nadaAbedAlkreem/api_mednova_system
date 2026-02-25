<?php

namespace App\Http\Requests\api\program;

use App\Services\Api\Customer\UploadService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramVideosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
//            'program_id' => 'required|exists:programs,id,deleted_at,NULL',
            'title_ar' => 'sometimes|string|max:255',
//            'videos.*.what_you_will_learn_en' => 'required|string|max:255',
            'description_ar' => 'sometimes|string|max:255',
//            'videos.*.description_en' => 'required|string|max:255',
            'duration_minute' => 'sometimes|integer|min:0',
            'order' => 'sometimes|integer|min:0',
 //            'video.title_en' => 'nullable|string|max:255',
            'video_path' => 'sometimes|file|mimes:mp4,mov,avi|max:512000',
            'is_program_intro' => 'sometimes|boolean',
            'is_free' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            // برنامج الفيديو
            'program_id.required' => __('validation.required', ['attribute' => __('validation.attributes.program_id')]),
            'program_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.program_id')]),

            'video.title_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.video_title_ar')]),
            'video.title_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.video_title_ar'), 'max' => 255]),

            'videos.*.description_ar.required' => __('validation.required', ['attribute' => __('validation.attributes.description_ar')]),
            'videos.*.description_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.description_ar')]),
            'videos.*.description_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.description_ar'), 'max' => 255]),

            'videos.*.what_you_will_learn_ar.required' => __('validation.required', ['attribute' => __('validation.attributes.what_you_will_learn_ar')]),
            'videos.*.what_you_will_learn_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.what_you_will_learn_ar')]),
            'videos.*.what_you_will_learn_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.what_you_will_learn_ar'), 'max' => 255]),

            'video.duration_minute.integer' => __('validation.integer', ['attribute' => __('validation.attributes.video_duration_minute')]),
            'video.duration_minute.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.video_duration_minute'), 'min' => 0]),

            'video.order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.video_order')]),
            'video.order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.video_order'), 'min' => 0]),

            'video.video_path.required' => __('validation.required', ['attribute' => __('validation.attributes.video_file')]),
            'video.video_path.file' => __('validation.file', ['attribute' => __('validation.attributes.video_file')]),
            'video.video_path.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.video_file'), 'values' => 'mp4,mov,avi']),
            'video.video_path.max' => __('validation.max.file', ['attribute' => __('validation.attributes.video_file'), 'max' => 512000]),
            'videos.*.is_program_intro.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_program_intro')]),
            'video.is_free.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.video_is_free')]),
        ];
    }


//    public function getData()
//    {
//        $uploadService = new UploadService();
//        $data= $this::validated();
//        if (isset($data['video_path'])) {
//                 if ($this->hasFile("video_path")) {
//                    $path = $uploadService->upload(
//                        $this->file("video_path"), 'program_video', 'public', 'videos');
//                    $data['video_path'] = asset('storage/' . $path);
//            }
//        }
//        return $data;
//    }



}
