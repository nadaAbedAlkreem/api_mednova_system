<?php

namespace App\Http\Controllers\Api\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StorePatientRequest;
use App\Http\Requests\api\user\UpdatePatientRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Patient;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IPatientRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class PatientController extends Controller
{
   use ResponseTrait;
    protected IPatientRepositories $patientRepository ;
    protected ICustomerRepositories $customerRepositories;
    /**
     * Display a listing of the resource.
     */



    public function __construct(IPatientRepositories $patientRepository , ICustomerRepositories $customerRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->patientRepository  = $patientRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\Routing\ResponseFactory
    {

        try {
            $this->customerRepositories->update($request->getData() ,$request['customer_id'] );
            $patient = $this->patientRepository->create($request->getData());
            if(!$patient instanceof Patient){
                throw new \Exception('Create Patient  Failed');
            }
            $patient->load('customer');
            $patient->customer->load('location');
            $patient->customer->load('patient');
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new CustomerResource($patient->customer), 201,);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500, app()->getLocale());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Patient $patient)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        //
    }
}
