<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\auth\ForgotPasswordRequest;
use App\Http\Requests\api\auth\ResetPasswordRequest;
use App\Http\Requests\api\auth\VerifyTokenRequest;
use App\Mail\RestPasswordMail;
use App\Models\Customer;
use App\Models\User;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    use ResponseTrait ;
    protected $client ;
    protected $maxAttempts = 5;



     public function sendResetLink(ForgotPasswordRequest $request)
     {
         $identifier = $this->getIdentifier($request);

         // Initialize attempts count if not already present in the cache
         if (!Cache::has($identifier . '_attempts')) {
             Cache::put($identifier . '_attempts', 0);
         }

         // Get the number of attempts from the cache
         $attempts = Cache::get($identifier . '_attempts', 0);

         // Get the lockout expiration time from the cache
         $lockoutExpiresAt = Cache::get($identifier . '_lockout_time');

         // If lockout is still in effect, return an error with remaining minutes
         if ($lockoutExpiresAt && Carbon::now()->lessThan(Carbon::parse($lockoutExpiresAt))) {
             $minutesRemaining = Carbon::now()->diffInMinutes(Carbon::parse($lockoutExpiresAt));
             return $this->errorResponse(__('messgas.TOO_MANY_ATTEMPTS'), ['error' => __('messages.TOO_MANY_ATTEMPTS')], 429, app()->getLocale());
         }

         // If maximum attempts are exceeded, set lockout time and reset attempts
         if ($attempts >= $this->maxAttempts) {
             // Set lockout expiration to 1 minute from now
             Cache::put($identifier . '_lockout_time', Carbon::now()->addMinutes(1), 1 * 60);
             Cache::forget($identifier . '_attempts');

             return $this->errorResponse(__('messages.TOO_MANY_ATTEMPTS'), ['error' => __('messages.TOO_MANY_ATTEMPTS')], 429, app()->getLocale());
         }

         // Look for the user based on the verification method (email or phone)
//         $user = $request->verification_method === 'email'
//             ? User::where('email', $request->email)->first()
//             : User::where('phone', $request->phone)->first();

         $customer =  Customer::where('email', $request->email)->first() ;


         // If user not found, return error
         if (!$customer) {
             return $this->errorResponse(__('messages.USER_NOT_FOUND'),[], 404, app()->getLocale());
         }

         // Generate and save a random token for password reset
         $token = random_int(1000, 9999);
         $customer->update(['remember_token' => $token]);

         // Set the expiration time for the token
         $utcNow = Carbon::now();
         $localNow = $utcNow->setTimezone('Asia/Baghdad');
         $expiryTime = $localNow->copy()->addMinutes(1);

         // Store token and expiry time in cache
         Cache::put($identifier . '_token', ['token' => $token, 'expires_at' => $expiryTime], 60);

         // Send the reset link via email or phone
         if ($request->verification_method === 'email') {
             Mail::to($customer->email)->queue(new RestPasswordMail($token));
         }
//         elseif ($request->verification_method === 'phone') {
//             $this->smsService->sendSms($user->phone, "Your verification code is: $token");
//         }

         // Increment the number of attempts in the cache
         Cache::increment($identifier . '_attempts');

         return $this->successResponse(__('messages.PASSWORD_RESET_LINK_SEND'), [], 201, app()->getLocale());
     }


     protected function getIdentifier($request)
    {
//        if ($request->verification_method === 'email') {
//            return 'password_reset_' . $request->email;
//        } elseif ($request->verification_method === 'phone') {
//            return 'password_reset_' . $request->phone;
//        }
//        return 'password_reset_' . $request->ip();
        return 'password_reset_' . $request->email;
    }


     public function verifyToken(VerifyTokenRequest $request)
     {
         $identifier = $this->getIdentifier($request);
         $cachedData = Cache::get($identifier . '_token');
          if (!$cachedData || Carbon::now()->setTimezone('Asia/Baghdad')->greaterThan(Carbon::parse($cachedData['expires_at']))) {
             return $this->errorResponse('TOKEN_EXPIRED', ['error'=> __('messages.TOKEN_EXPIRED')], 400, app()->getLocale());
         }
         $customer = Customer::where('email', $request->email)
             ->orWhere('phone', $request->phone)
             ->first();
         if (!$customer) {
             return $this->errorResponse('USER_NOT_FOUND', [], 404, app()->getLocale());
         }
         if ($cachedData['token'] == $request->token) {
             return $this->successResponse(__('messages.TOKEN_VALID'), ['error'=> __('messages.TOKEN_VALID')], 200, app()->getLocale());
         } else {
             return $this->errorResponse(__('messages.INVALID_TOKEN'), ['error'=> __('messages.INVALID_TOKEN')], 400, app()->getLocale());
         }
     }

     public function resetPassword(ResetPasswordRequest $request)
    {


        $customer = Customer::where('email', $request->email)
                    ->orWhere('phone', $request->phone)
                    ->where('remember_token', $request->token)
                    ->first();

        if (!$customer) {
            return $this->errorResponse(__('messages.INVALID_TOKEN'),['error'=> __('messages.TOKEN_VALID')], 404, app()->getLocale());
         }

        $customer->password = Hash::make($request->password);
        $customer->remember_token = null;
        $customer->save();

        return $this->successResponse(__('messages.PASSWORD_RESET_SUCCESSFUL'), [], 202, app()->getLocale());
    }
}
