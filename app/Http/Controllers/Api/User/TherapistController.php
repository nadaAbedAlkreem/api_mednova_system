<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreTherapistRequest;
use App\Http\Requests\api\user\UpdateTherapistRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\TherapistResource;
use App\Models\Therapist;
use App\Repositories\ICustomerRepositories;
use App\Repositories\ITherapistRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class TherapistController extends Controller
{
    use ResponseTrait;
    protected  ITherapistRepositories $therapistRepositories;
    protected ICustomerRepositories $customerRepositories;

    public function __construct(ITherapistRepositories $therapistRepositories , ICustomerRepositories $customerRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->therapistRepositories = $therapistRepositories;
    }

    public function store(StoreTherapistRequest $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
                $this->customerRepositories->update($request->getData() ,$request['customer_id'] );
                $therapist = $this->therapistRepositories->create($request->getData());
                if(!$therapist instanceof Therapist){
                    throw new \Exception('Create Therapists  Failed');
                }
                $therapist->load('customer');
                $therapist->customer->load(['location' ,'therapist' ,'therapist.specialty']);
                DB::commit();
                return $this->successResponse(__('messages.CREATE_SUCCESS'), new CustomerResource($therapist->customer), 201,);
            } catch (\Exception $e) {
                 DB::rollback();
                return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500, app()->getLocale());
            }
    }



    /**
     * Display the specified resource.
     */
    public function show(Therapist $therapist)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Therapist $therapist)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTherapistRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $request->getData();
            $customerData = array_intersect_key($data, array_flip(['full_name', 'email', 'phone', 'image', 'gender']));
            $this->customerRepositories->update($customerData,$request['customer_id'] );
            $therapistData = array_intersect_key($data, array_flip(['medical_specialties_id', 'experience_years', 'university_name', 'countries_certified', 'graduation_year', 'certificate_file', 'license_number', 'license_authority', 'license_file', 'bio',]));
            $therapistData = array_filter($therapistData, fn($value) => !is_null($value) && $value !== '');
            $this->therapistRepositories->updateWhere($therapistData, ['customer_id' => $request['customer_id']]);
            return $this->successResponse(__('messages.UPDATE_SUCCESS'),[], 201,);
        }catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Therapist $therapist)
    {
        //
    }
}
