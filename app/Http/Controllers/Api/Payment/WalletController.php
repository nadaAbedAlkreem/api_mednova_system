<?php

namespace App\Http\Controllers\Api\Payment;

use App\Enums\ConsultantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Financial\ConsultantTransactionResource;
use App\Http\Resources\Api\Financial\Wallet\ConsultantWalletResource;
use App\Http\Resources\Api\Financial\Wallet\PatientWalletResource;
use App\Http\Resources\Api\Financial\WalletResource;
use App\Models\Wallet;
use App\Services\Api\Financial\ConsultantFinancialService;
use App\Services\Api\Financial\PatientFinancialService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    use ResponseTrait;
    public function __construct(
        private readonly ConsultantFinancialService $consultantFinancialService ,
        private readonly PatientFinancialService $patientFinancialService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function walletConsultant(Request $request): JsonResponse
    {
        try {
            $consultant = $request->user('api');
            $wallet = $this->consultantFinancialService->getWallet($consultant);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),new  ConsultantWalletResource($wallet), 202);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }

    }
    public function walletPatient(Request $request): JsonResponse
    {
        try {
            $patient = $request->user('api');
            $wallet  = $this->patientFinancialService->getWallet($patient);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),new  PatientWalletResource($wallet), 202);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }


    }

    /**
     * Show the form for creating a new resource.
     */


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWalletRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Wallet $wallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Wallet $wallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWalletRequest $request, Wallet $wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wallet $wallet)
    {
        //
    }
}
