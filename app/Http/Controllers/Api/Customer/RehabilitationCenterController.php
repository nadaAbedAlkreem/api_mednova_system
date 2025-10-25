<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreRehabilitationCenterRequest;
use App\Http\Requests\api\user\UpdateRehabilitationCenterRequest;
use App\Http\Requests\api\user\UpdateScheduleRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Models\RehabilitationCenter;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use App\Services\api\RehabilitationCenterService;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class RehabilitationCenterController extends Controller
{
    use ResponseTrait ;
    protected ICustomerRepositories $customerRepositories;
    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;
    protected ITherapistRepositories $therapistRepositories;
    protected IScheduleRepositories $scheduleRepositories;


    public function __construct(IScheduleRepositories $scheduleRepositories ,ICustomerRepositories $customerRepositories, ITherapistRepositories $therapistRepositories , IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
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
    public function store(StoreRehabilitationCenterRequest $request, RehabilitationCenterService $service): \Illuminate\Http\JsonResponse
    {
    try {
        $data = $request->getData();
        $customer = $service->store($data, $request['customer_id'], $request['specialty_id']);
        if(!$customer instanceof Customer ){
            throw new \Exception('Create Customer Failed');

        }
        return $this->successResponse(__('messages.CREATE_SUCCESS'),new CustomerResource($customer), 201,);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RehabilitationCenter $rehabilitationCenter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RehabilitationCenter $rehabilitationCenter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRehabilitationCenterRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $data = $request->getData();
            $this->customerRepositories->update($data['customer']->toArray(),$request['customer_id'] );
            $this->rehabilitationCenterRepositories->updateWhere($data['center']->toArray(),['customer_id'=>$request['customer_id']] );
            if (!empty($request['specialty_id'])) {
                $center = $this->customerRepositories->findOrFail($request['customer_id'] );
                $center->medicalSpecialties()->sync($request['specialty_id']);
            }
            DB::commit();
            return $this->successResponse(__('messages.UPDATE_SUCCESS'),[], 200,);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RehabilitationCenter $rehabilitationCenter)
    {
        //
    }
}
