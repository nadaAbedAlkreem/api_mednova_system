<?php

namespace App\Http\Requests\api\user;

use Illuminate\Foundation\Http\FormRequest;

class StoreTherapistRequest extends FormRequest
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
            'gender' => 'required',
            'birth_date' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'experience_years' => 'required',
            'university_name' => 'required',
            'countries_certified' => 'required',
            'graduation_year' => 'required',
            'certificate_file'=>'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'license_number'   =>'required',
            'license_authority'=>'required',
            'license_file'=>'required',
            'bio'=>'required',
            'medical_specialties'=>'required',


        ];
    }
}
