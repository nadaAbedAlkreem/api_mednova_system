<?php

namespace App\Http\Controllers\Api\Payment;

use App\Enums\ConsultantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Financial\ConsultantTransactionResource;
use App\Http\Resources\Api\Financial\WalletResource;
use App\Models\Wallet;
use App\Services\Api\Financial\ConsultantFinancialService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    use ResponseTrait;
    public function __construct(
        private readonly ConsultantFinancialService $financialService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function walletConsultant(Request $request): JsonResponse
    {
        try {
            $consultant = $request->user('api');
            $wallet = $this->financialService->getWallet($consultant);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),new  WalletResource($wallet), 202);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }

    }
    public function walletPatient(Request $request): JsonResponse
    {
        $patient = $request->user();
        $wallet  = $this->financialService->getWallet($patient);

        if (! $wallet) {
            $wallet = new Wallet([
                'available_balance' => '0.000',
                'currency'          => 'OMR',
            ]);
        }

        return response()->json([
            'data' => WalletResource::make($wallet)->forPatient(),
        ]);
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
