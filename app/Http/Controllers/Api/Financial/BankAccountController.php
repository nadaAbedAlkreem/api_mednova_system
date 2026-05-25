<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Financial\StoreBankAccountRequest;
use App\Http\Requests\Api\Financial\UpdateBankAccountRequest;
use App\Http\Requests\Api\Financial\VerifyBankAccountOtpRequest;
use App\Http\Resources\Api\Financial\BankAccountResource;
use App\Services\Api\Financial\BankAccount\BankAccountService;
use App\Traits\ResponseTrait;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected BankAccountService $bankAccountService,
    ) {}

    /**
     * Add bank account
     *
     * Register a new bank account for the authenticated consultant.
     * A 6-digit OTP will be sent to the account email for verification.
     * Only one bank account is allowed per consultant — call update to change it.
     *
     * @tags Financial — Bank Account
     * @response 201 scenario="Created" {"success":true,"message":"تم إضافة الحساب البنكي بنجاح","data":{"id":1,"bank_name":"Bank Muscat","account_holder_name":"Ahmed Al-Balushi","account_number":"****5678","iban":"****123456","swift_code":"BMUSOMRX","bank_country":"OM","status":"pending","status_label":"قيد التحقق","is_default":true,"verified_at":null,"created_at":"2026-05-09T10:00:00+00:00"}}
     * @response 422 scenario="Validation Error" {"success":false,"message":"حدث خطأ","data":{"bank_name":"اسم البنك مطلوب."}}
     */
    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        try {
            $bankAccount = $this->bankAccountService->store(
                $request->user('api'),
                $request->validated()
            );
            return $this->successResponse(
                __('messages.BANK_ACCOUNT_CREATED_SUCCESSFULLY'),
                new BankAccountResource($bankAccount),
                201
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get bank account
     *
     * Retrieve the authenticated consultant's registered bank account details.
     * Account number and IBAN are masked for security (only last 4/6 digits shown).
     *
     * @tags Financial — Bank Account
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":{"id":1,"bank_name":"Bank Muscat","account_holder_name":"Ahmed Al-Balushi","account_number":"****5678","iban":"****123456","swift_code":"BMUSOMRX","bank_country":"OM","status":"verified","status_label":"تم التحقق","is_default":true,"verified_at":"2026-05-09T11:00:00+00:00","created_at":"2026-05-09T10:00:00+00:00"}}
     * @response 404 scenario="Not Found" {"success":false,"message":"الحساب البنكي غير موجود","data":[]}
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $bankAccount = $this->bankAccountService->getUserBankAccount($request->user('api'));

            if (!$bankAccount) {
                return $this->errorResponse(__('messages.BANK_ACCOUNT_NOT_FOUND'), [], 404);
            }

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                new BankAccountResource($bankAccount),
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update bank account
     *
     * Update the authenticated consultant's bank account details.
     * Updating resets verification status to **pending** and sends a new OTP to the account email.
     * Only include fields that need to change (partial update supported).
     *
     * @tags Financial — Bank Account
     * @response 200 scenario="Updated" {"success":true,"message":"تم تحديث الحساب البنكي بنجاح","data":{"id":1,"bank_name":"Bank Dhofar","account_holder_name":"Ahmed Al-Balushi","account_number":"****9012","iban":"****789012","swift_code":"BDHOOMRX","bank_country":"OM","status":"pending","status_label":"قيد التحقق","is_default":true,"verified_at":null,"created_at":"2026-05-09T10:00:00+00:00"}}
     * @response 422 scenario="Validation Error" {"success":false,"message":"حدث خطأ","data":{"account_number":"رقم الحساب يجب أن يكون 8 أحرف على الأقل."}}
     */
    public function update(UpdateBankAccountRequest $request): JsonResponse
    {
        try {
            $bankAccount = $this->bankAccountService->update(
                $request->user('api'),
                $request->validated()
            );

            return $this->successResponse(
                __('messages.BANK_ACCOUNT_UPDATED_SUCCESSFULLY'),
                new BankAccountResource($bankAccount),
                200
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify bank account OTP
     *
     * Submit the 6-digit OTP received by email to verify and activate the bank account.
     * OTP expires after the configured TTL (default: 10 minutes).
     * After successful verification, the account status changes from **pending** to **verified**.
     *
     * @tags Financial — Bank Account
     * @response 200 scenario="Verified" {"success":true,"message":"تم التحقق من الحساب البنكي بنجاح","data":{"id":1,"bank_name":"Bank Muscat","account_holder_name":"Ahmed Al-Balushi","account_number":"****5678","iban":"****123456","swift_code":"BMUSOMRX","bank_country":"OM","status":"verified","status_label":"تم التحقق","is_default":true,"verified_at":"2026-05-09T11:05:00+00:00","created_at":"2026-05-09T10:00:00+00:00"}}
     * @response 422 scenario="Invalid or Expired OTP" {"success":false,"message":"رمز التحقق غير صحيح أو منتهي الصلاحية","data":[]}
     */
    public function verifyOtp(VerifyBankAccountOtpRequest $request): JsonResponse
    {
        try {
            $bankAccount = $this->bankAccountService->verifyOtp(
                $request->user('api'),
                $request->validated()['otp']
            );

            return $this->successResponse(
                __('messages.BANK_ACCOUNT_VERIFIED_SUCCESSFULLY'),
                new BankAccountResource($bankAccount),
                200
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
    public function resendOtp(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\Customer $user */
            $user = $request->user();
            $this->bankAccountService->resendOtp($user);

            return response()->json([
                'success' => true,
                'message' => __('messages.OTP_RESENT_SUCCESSFULLY'),
            ], 200);

        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
