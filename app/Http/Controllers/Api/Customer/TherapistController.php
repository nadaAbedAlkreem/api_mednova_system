<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\ConsultantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreTherapistRequest;
use App\Http\Requests\api\user\UpdateTherapistRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Models\Therapist;
use App\Repositories\ICustomerRepositories;
use App\Repositories\ILocationRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use App\Services\Api\Consultation\SchedulerService;
use App\Services\Api\Customer\TherapistService;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class TherapistController extends Controller
{
    use ResponseTrait;
    protected  ITherapistRepositories $therapistRepositories;
    protected  IScheduleRepositories $scheduleRepositories;
    protected ICustomerRepositories $customerRepositories;
    protected ILocationRepositories $locationRepositories;
    protected SchedulerService $schedulerService;
    protected TherapistService $therapistService;


    public function __construct(TherapistService $therapistService , SchedulerService $schedulerService ,ILocationRepositories $locationRepositories , IScheduleRepositories $scheduleRepositories , ITherapistRepositories $therapistRepositories , ICustomerRepositories $customerRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
        $this->locationRepositories = $locationRepositories;
        $this->schedulerService = $schedulerService;
        $this->therapistService = $therapistService;
    }

    public function store(StoreTherapistRequest $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
//            $data = $request->getData();
            $data = $this->therapistService->prepare($request->validated(),null);
            $therapist = $this->therapistService->store($data ,$request['customer_id'] );
//            $this->customerRepositories->update($data['customer'],$request['customer_id'] );
//            $this->locationRepositories->create($data['location']);
//            $therapist = $this->therapistRepositories->create($data['therapist']);
//            $this->scheduleRepositories->create($data['schedule']);
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
            $data = $this->therapistService->prepare($request->validated(),null);
//            $customerData = array_intersect_key($data, array_flip(['full_name', 'email', 'birth_date', 'phone', 'image', 'gender' , 'timezone']));
            $this->customerRepositories->update($data['customer'],$request['customer_id'] );
//            $therapistData = array_intersect_key($data, array_flip(['medical_specialties_id', 'experience_years', 'university_name', 'countries_certified', 'graduation_year','video_consultation_price' , 'certificate_file', 'license_number', 'license_authority', 'license_file', 'bio', 'video_consultation_price' , 'chat_consultation_price']));
//            $therapistData = array_filter($therapistData, fn($value) => !is_null($value) && $value !== '');
            $this->therapistRepositories->updateWhere($data['therapist'], ['customer_id' => $request['customer_id']]);
            $this->locationRepositories->updateWhere($data['location'],['customer_id'=>$request['customer_id']] );
            if (!empty($data['schedule'])) {
                $hasActiveConsultation = Customer::where('id', $request['customer_id'])
                    ->where(function ($query) {
                        $query->whereHas('receivedConsultations', function ($q) {
                            $q->where('consultant_type', ConsultantType::REHABILITATION_CENTER)
                                ->whereIn('status', ['active', 'accepted', 'pending']);
                        })
                            ->orWhereHas('consultationVideoRequestsForConsultant', function ($q) {
                                $q->where('consultant_type', ConsultantType::REHABILITATION_CENTER)
                                    ->whereIn('status', ['active', 'accepted', 'pending']);
                            });
                    })
                    ->exists();
                if ($hasActiveConsultation) {
                    throw new \Exception('لا يمكن تحديث المواعيد بسبب وجود استشارات نشطة أو مقبولة أو معلقة.');
                }
                $this->schedulerService->update(
                    $request->customer_id,
                    ConsultantType::THERAPIST,
                    $data['schedule']
                );
            }
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
