<?php

namespace App\Http\Requests\api\device;

use App\Models\GloveData;
use App\Models\GloveDevice;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class StoreGloveDataRequest extends FormRequest
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
            'device_id'     => ['required', 'integer'],
            'glove_id'      => ['required','exists:glove_devices,id,deleted_at,NULL'],
            'status'        => ['required', 'integer', 'between:1,4'],
            'flex_thumb'    => ['nullable', 'integer', 'between:0,1023'],
            'flex_index'    => ['nullable', 'integer', 'between:0,1023'],
            'flex_middle'   => ['nullable', 'integer', 'between:0,1023'],
            'flex_ring'     => ['nullable', 'integer', 'between:0,1023'],
            'flex_pinky'    => ['nullable', 'integer', 'between:0,1023'],
            'heartbeat'     => ['nullable', 'integer', 'between:30,180'],
            'temperature'   => ['nullable', 'numeric', 'between:20,50'],
            'resistance'    => ['nullable', 'numeric', 'min:0'],
            'error_flag'    => ['required', 'integer', 'between:0,1'],
            'crc_valid'     => ['required', 'boolean'],
            'serial_number' => ['required']
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->messages();
        $formattedErrors = [];

        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0];
        }
        $hasOnlyGloveIdError = array_key_exists('glove_id', $formattedErrors)
            && count($formattedErrors) === 1;

        try {  // ✅ إذا كان الخطأ مش فقط glove_id → نسجله
            $gloveErrorRepo = app(IGloveErrorRepositories::class);
            if (! $hasOnlyGloveIdError) {
                    $gloveId = $this->input('glove_id') ?? null;
                    $gloveErrorRepo->storeGloveError(
                        json_encode($formattedErrors, JSON_UNESCAPED_UNICODE),
                        $gloveId,
                        null,
                        GloveError::UNKNOWN
                    );

            }else
            {
                $gloveErrorRepo->storeGloveError(
                    json_encode($formattedErrors, JSON_UNESCAPED_UNICODE),
                    null,
                    null,
                    GloveError::UNKNOWN
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to store glove error: ' . $e->getMessage());
        }

        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => __('messages.ERROR_OCCURRED'),
            'data' => $formattedErrors,
            'status' => 'Internal Server Error'
        ], 422));
    }

}
