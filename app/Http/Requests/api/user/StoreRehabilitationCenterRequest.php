<?php

namespace App\Http\Requests\api\user;

use Illuminate\Foundation\Http\FormRequest;

class StoreRehabilitationCenterRequest extends FormRequest
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
         'user_id' => 'required',
         'year_establishment'=>'required' ,
         'license_number'=>'required',
          'license_authority'=>'required',
          'license_file'=>'required',
          'has_commercial_registration'=>'required',
          'commercial_registration_number'=>'required',
          'commercial_registration_file'=>'required',
          'specialty_id'=>'required',




        ];
    }
}
