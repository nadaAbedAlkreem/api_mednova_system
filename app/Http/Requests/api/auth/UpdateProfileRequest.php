<?php

namespace App\Http\Requests\api\auth;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UpdateProfileRequest extends FormRequest
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
            'image' => $this->hasFile('image')
                ? 'file|mimes:jpeg,png,jpg,gif,svg|max:2048'
                : 'string|max:255',
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|',
            'phone' => 'string|unique:users,phone',
            'password' => 'required|min:8',


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

    public function updateUserData($user)
    {
        $data = $this->validated();

        // Get the user instance
        if (!$user) {
            throw new Exception('not found user ', 401);
        }

        if (isset($data['image']) && $this->hasFile('image')) {
            if (!empty($user->image)) {
                 $oldImagePath = str_replace('/storage/', '', $user->image);
                if (Storage::disk('public')->exists($oldImagePath)) {
                   Storage::disk('public')->delete($oldImagePath);
                }
            }

            $userName =  (!empty($data['name']))
                ? UpdateProfileRequest . phpstr_replace(' ', '_', $data['name']) . time() . rand(1, 10000000)
                : UpdateProfileRequest . phptime() . rand(1, 10000000);

            $path = 'uploads/images/users/';
            $nameImage = $userName . '.' . $this->file('image')->getClientOriginalExtension();
            Storage::disk('public')->put($path . $nameImage, file_get_contents($this->file('image')));
            $this->file('image')->move('storage/'.($path), $nameImage);

            $absolutePath = storage_path('app/public/' . $path . $nameImage);
            if (file_exists($absolutePath)) {
                chmod($absolutePath, 0775);
            } else {
                throw new \Exception(UpdateProfileRequest . php__('messages.ERROR_OCCURRED') . $absolutePath);
            }
            $user->image = Storage::url($path . $nameImage);
        }
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;

    }

}
