<?php

namespace App\Http\Requests\api\device;

use App\Models\GloveCommand;
use Illuminate\Foundation\Http\FormRequest;

class StoreGloveCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return  true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'glove_command' => 'required|string|in:OPEN_HAND,PING,CLOSE_HAND,GRIP,RELAX,SET_FINGER,STOP,RESET',
            'repeat' => 'integer|nullable',
            'rest_time' => 'integer|nullable',



        ];
    }
}
