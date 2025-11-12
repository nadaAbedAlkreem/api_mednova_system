<?php

namespace App\Http\Requests\api\device;

use App\Models\Customer;
use App\Models\Device;
use App\Models\DeviceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeviceRequestRequest extends FormRequest
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

    public function withValidator($validator)
    {
       return $validator->after(function (Validator $validator) {
           $customerId = $this->input('customer_id');
           $deviceId = $this->input('device_id');
            $customer = Customer::with(['location','patient'])->find($customerId);
            if (!$customer) {
                $validator->errors()->add('customer_id', 'قيمة المستخدم غير موجود.');
            }
            if (is_null($customer->location) || is_null($customer->patient)) {
                $validator->errors()->add('customer_id', 'يوجد بيانات غير موجودة خاصة في الزبون يجب توفيرها مثل رقم تواصل و مثل بيانات الموقع .');
            }
            $device = Device::find($deviceId);
           if (!$device) {
               $validator->errors()->add('device_id', 'قيمة الجهاز غير موجودة.');
           }
           $deviceRequest = DeviceRequest::where(['customer_id' => $customerId, 'device_id' =>$deviceId , 'status' => 'pending'])->exists();
           if ($deviceRequest) {
                   $validator->errors()->add('duplicate_request', __('messages.duplicate_request'));
           }
        });
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id,deleted_at,NULL'],
            'device_id' => ['required', 'exists:devices,id,deleted_at,NULL'],
            'status' => ['nullable', Rule::in(['pending', 'in_contact', 'approved', 'rejected', 'delivered'])],
            'request_date' => ['nullable', 'date'],
            'contact_status' => ['nullable', Rule::in(['pending', 'contacted', 'no_response'])],
            'notes' => ['nullable', 'string', 'max:1000'],
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

    public function messages(): array
    {
        return [
            'customer_id.required' => __('validation.required', ['attribute' => __('validation.attributes.customer_id')]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.customer_id')]),

            'device_id.required' => __('validation.required', ['attribute' => __('validation.attributes.device_id')]),
            'device_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.device_id')]),

        ];
    }
   public function getData(): array
   {

       $data = $this->validated();
       if (empty($data['request_date'])) {
           $data['request_date'] = Carbon::now();
       }
       return $data;

   }



}
