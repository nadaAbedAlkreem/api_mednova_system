<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreTherapistRequest;
use App\Http\Requests\api\user\UpdateTherapistRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Therapist;
use App\Repositories\ICustomerRepositories;
use App\Repositories\ILocationRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class TherapistController extends Controller
{
    use ResponseTrait;
    protected  ITherapistRepositories $therapistRepositories;
    protected  IScheduleRepositories $scheduleRepositories;
    protected ICustomerRepositories $customerRepositories;
    protected ILocationRepositories $locationRepositories;

    public function __construct( ILocationRepositories $locationRepositories , IScheduleRepositories $scheduleRepositories , ITherapistRepositories $therapistRepositories , ICustomerRepositories $customerRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
        $this->locationRepositories = $locationRepositories;
    }

    public function store(StoreTherapistRequest $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->getData();
            $this->customerRepositories->update($data['data']->toArray(),$request['customer_id'] );
            $this->locationRepositories->create($data['data']->toArray());
            $therapist = $this->therapistRepositories->create($data['data']->toArray(),);
            $this->scheduleRepositories->create($data['schedule']->toArray());
            $therapist->load('customer');
            $therapist->customer->load(['location' ,'schedules','therapist' ,'therapist.specialty']);
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
            $customerData = array_intersect_key($data, array_flip(['full_name', 'email', 'birth_date', 'phone', 'image', 'gender']));
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
