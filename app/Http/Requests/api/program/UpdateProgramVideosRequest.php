<?php

namespace App\Http\Requests\api\program;

use App\Services\api\UploadService;
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'video_id' => 'required|exists:program_videos,id',
            'video.title_ar' => 'required|string|max:255',
            'video.duration_minute' => 'nullable|integer|min:0',
            'video.order' => 'nullable|integer|min:0',
//            'video.is_preview' => 'nullable|boolean',
//            'video.title_en' => 'nullable|string|max:255',
            'video.video_file' => 'required|file|mimes:mp4,mov,avi|max:512000',
            'video.is_free' => 'nullable|boolean',
        ];
    }
    public function getData()
    {
        $uploadService = new UploadService();
        $data= $this::validated();
        if (isset($data['video_file'])) {
                 if ($this->hasFile("video_file")) {
                    $path = $uploadService->upload(
                        $this->file("video_file"), 'program_video', 'public', 'videos');
                    $data['video_file'] = asset('storage/' . $path);
            }
        }
        return $data;
    }



}
