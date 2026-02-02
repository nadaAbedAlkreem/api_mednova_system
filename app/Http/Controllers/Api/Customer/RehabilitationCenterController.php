<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\ConsultantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreRehabilitationCenterRequest;
use App\Http\Requests\api\user\UpdateRehabilitationCenterRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Models\RehabilitationCenter;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use App\Services\Api\Consultation\SchedulerService;
use App\Services\Api\Customer\RehabilitationCenterService;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class RehabilitationCenterController extends Controller
{
    use ResponseTrait ;
    protected ICustomerRepositories $customerRepositories;
    protected SchedulerService $schedulerService;
    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;
    protected ITherapistRepositories $therapistRepositories;
    protected IScheduleRepositories $scheduleRepositories;
    protected RehabilitationCenterService $rehabilitationCenterService;

    public function __construct(RehabilitationCenterService $rehabilitationCenterService,SchedulerService $schedulerService ,IScheduleRepositories $scheduleRepositories ,ICustomerRepositories $customerRepositories, ITherapistRepositories $therapistRepositories , IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
        $this->schedulerService = $schedulerService;
        $this->rehabilitationCenterService = $rehabilitationCenterService;
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRehabilitationCenterRequest $request): \Illuminate\Http\JsonResponse
    {
    try {
        $data = $request->getData();
        $customer = $this->rehabilitationCenterService->store($data, $request['customer_id'], $request['specialty_id']);
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
     * Update the specified resource in storage.
     */
    public function update(UpdateRehabilitationCenterRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $authUser = auth('api')->user();

            if (empty($authUser->timezone)) {
                throw new \RuntimeException("User timezone is required for this operation.");
            }

            $authUserTimezone = $authUser->timezone;
            $data = $this->rehabilitationCenterService->prepare($request->validated(), $authUserTimezone);
            $this->customerRepositories->update($data['customer']->toArray(),$request['customer_id'] );
            $this->rehabilitationCenterRepositories->updateWhere($data['center']->toArray(),['customer_id'=>$request['customer_id']] );
            if (!empty($request['specialty_id'])) {
                $center = $this->customerRepositories->findOrFail($request['customer_id'] );
                $center->medicalSpecialties()->sync($request['specialty_id']);
            }
            if (!empty($data['schedule'])) {
                $this->schedulerService->update(
                    $request->customer_id,
                    ConsultantType::REHABILITATION_CENTER,
                    $data['schedule']->toArray()
                );
            }
            DB::commit();
            return $this->successResponse(__('messages.UPDATE_SUCCESS'),[], 200,);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
 }
