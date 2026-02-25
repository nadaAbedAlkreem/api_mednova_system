<?php

namespace App\Http\Requests\api\program;

use App\Services\Api\Customer\UploadService;
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
            'creator_id' => 'sometimes|exists:admins,id,deleted_at,NULL',
            'title_ar' => 'sometimes|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'what_you_will_learn_ar' => 'nullable|string',
            'what_you_will_learn_en' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:50',
            'is_approved' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'creator_id.required' => __('validation.required', ['attribute' => __('validation.attributes.creator_id')]),
            'creator_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.creator_id')]),

            'creator_type.string' => __('validation.string', ['attribute' => __('validation.attributes.creator_type')]),
            'creator_type.max' => __('validation.max.string', ['attribute' => __('validation.attributes.creator_type'), 'max' => 255]),

            'title_ar.required' => __('validation.required', ['attribute' => __('validation.attributes.title_ar')]),
            'title_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.title_ar')]),
            'title_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title_ar'), 'max' => 255]),

            'title_en.string' => __('validation.string', ['attribute' => __('validation.attributes.title_en')]),
            'title_en.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title_en'), 'max' => 255]),

            'description_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.description_ar')]),
            'description_en.string' => __('validation.string', ['attribute' => __('validation.attributes.description_en')]),

            'what_you_will_learn_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.what_you_will_learn_ar')]),
            'what_you_will_learn_en.string' => __('validation.string', ['attribute' => __('validation.attributes.what_you_will_learn_en')]),

            'cover_image.image' => __('validation.image', ['attribute' => __('validation.attributes.cover_image')]),
            'cover_image.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.cover_image'), 'values' => 'jpeg,png,jpg,gif,svg']),
            'cover_image.max' => __('validation.max.file', ['attribute' => __('validation.attributes.cover_image'), 'max' => 10240]),

            'price.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.price')]),
            'price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.price'), 'min' => 0]),

            'currency.string' => __('validation.string', ['attribute' => __('validation.attributes.currency')]),
            'currency.max' => __('validation.max.string', ['attribute' => __('validation.attributes.currency'), 'max' => 10]),

            'status.string' => __('validation.string', ['attribute' => __('validation.attributes.status')]),
            'status.max' => __('validation.max.string', ['attribute' => __('validation.attributes.status'), 'max' => 50]),

            'is_approved.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_approved')]),

            // ProgramVideos
            'videos.array' => __('validation.array', ['attribute' => __('validation.attributes.videos')]),

            'videos.*.title_ar.required' => __('validation.required', ['attribute' => __('validation.attributes.video_title_ar')]),
            'videos.*.title_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.video_title_ar')]),
            'videos.*.title_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.video_title_ar'), 'max' => 255]),

            'videos.*.title_en.string' => __('validation.string', ['attribute' => __('validation.attributes.video_title_en')]),
            'videos.*.title_en.max' => __('validation.max.string', ['attribute' => __('validation.attributes.video_title_en'), 'max' => 255]),

            'videos.*.description_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.video_description_ar')]),
            'videos.*.description_en.string' => __('validation.string', ['attribute' => __('validation.attributes.video_description_en')]),

            'videos.*.what_you_will_learn_ar.string' => __('validation.string', ['attribute' => __('validation.attributes.video_what_you_will_learn_ar')]),
            'videos.*.what_you_will_learn_en.string' => __('validation.string', ['attribute' => __('validation.attributes.video_what_you_will_learn_en')]),

            'videos.*.video_path.required' => __('validation.required', ['attribute' => __('validation.attributes.video_path')]),
            'videos.*.video_path.file' => __('validation.file', ['attribute' => __('validation.attributes.video_path')]),
            'videos.*.video_path.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.video_path')]),
            'videos.*.video_path.max' => __('validation.max.string', ['attribute' => __('validation.attributes.video_path'), 'max' => 255]),

            'videos.*.duration_minute.required' => __('validation.required', ['attribute' => __('validation.attributes.video_duration_minute')]),
            'videos.*.duration_minute.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.video_duration_minute')]),
            'videos.*.duration_minute.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.video_duration_minute'), 'min' => 1]),

            'videos.*.order.integer' => __('validation.integer', ['attribute' => __('validation.attributes.video_order')]),
            'videos.*.order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.video_order'), 'min' => 1]),

            'videos.*.is_program_intro.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_program_intro')]),
            'videos.*.is_free.boolean' => __('validation.boolean', ['attribute' => __('validation.attributes.is_free')]),

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
