<?php

namespace App\Http\Controllers\Api\Auth;

use App;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Traits\ResponseTrait;
use Google_Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


class SocialAuthController extends Controller
{
    use ResponseTrait ;

    public function handleSocialLogin(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:google,facebook',
            'id_token' => 'required|string',
        ]);

        $provider = $request->provider;
        $idToken = $request->id_token;

        if ($provider === 'google')
        {
            $client = new Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return response()->json(['error' => 'Invalid Google token'], 401);
            }
            $socialId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? 'No Name';

        } else if ($provider === 'facebook') {
            try {
                $fbUser = Socialite::driver('facebook')->stateless()->userFromToken($idToken);

                $socialId = $fbUser->getId();
                $email = $fbUser->getEmail();
                $name = $fbUser->getName();

            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid Facebook token', 'message' => $e->getMessage()], 401);
            }

        } else {
            return response()->json(['error' => 'Unsupported provider'], 400);
        }

        $customer = Customer::where('provider', $provider)
            ->where('provider_id', $socialId)
            ->orWhere('email', $email)
            ->first();

        if (!$customer) {
                $customer = Customer::create([
                'full_name' => $name,
                'email' => $email,
                'provider' => $provider,
                'provider_id' => $socialId,
                'password' => Hash::make(Str::random(24)),
            ]);
        }

        Auth::login($customer);
        $token = $customer->createToken(ucfirst($provider) . 'AuthToken')->plainTextToken;
        return $this->successResponse('LOGGED_IN_SUCCESSFULLY', ['access_token' => $token, 'token_type' => 'Bearer', 'customer' => new CustomerResource($customer),], 202, app()->getLocale());

    }



}
