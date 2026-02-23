<?php

namespace App\Http\Controllers\Api\Auth;

use App;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\auth\RegisterUserRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Repositories\Eloquent\CustomerRepository;
use App\Services\Api\Customer\CustomerService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{

     use ResponseTrait ;
     protected CustomerService $customerService ;
     protected CustomerRepository $customerRepository;

    public function __construct(CustomerService $customerService , CustomerRepository $customerRepository)
    {
         $this->customerService = $customerService;
         $this->customerRepository = $customerRepository;
    }


    public function register(RegisterUserRequest $request): JsonResponse
    {
        try {
           DB::beginTransaction();
            $response = $this->customerService->register($request->getData());
            $customer = $response['customer'] ;
            if(!$customer instanceof App\Models\Customer){
                throw new \Exception('User Registration Failed');
            }
            $token = encrypt($customer->id);
            $url = url("api/auth/verify-email?token={$token}");
            Mail::to($response['customer'])->queue(new App\Mail\VerificationEmailMail($customer,$url));
            DB::commit();
            return $this->successResponse('CREATE_USER_SUCCESSFULLY', ['access_token' =>  $response['access_token'], 'user' => new CustomerResource($response['customer']),], 201, app()->getLocale());
        } catch (\Exception $e) {
            DB::rollBack();
             return $this->errorResponse('ERROR_OCCURRED', ['error' => $e->getMessage()], 500, app()->getLocale());
        }
     }


    public function verifyEmail(Request $request)
   {
       try {
           $token = $request->query('token');
           $customerId = decrypt($token);
           $customer = $this->customerRepository->findOrFail($customerId);
           if ($customer->email_verified_at) {return redirect()->to(config('app.frontend_url') . '/login?message=already_verified');}
           $customer->email_verified_at = now();
           $customer->save();
           return redirect()->to(config('app.frontend_url') . '/login?message=verified');
       }catch (\Exception $exception){
           return $this->errorResponse('ERROR_OCCURRED', ['error' => $exception->getMessage()], 500, app()->getLocale());
       }
   }
}
