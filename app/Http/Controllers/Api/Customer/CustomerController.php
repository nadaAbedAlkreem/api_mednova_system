<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\UpdateTimeZoneRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Repositories\ICustomerRepositories;
use App\Repositories\ILocationRepositories;
use App\Repositories\IPatientRepositories;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\ITherapistRepositories;
use App\Services\Api\Customer\SearchServiceProviderService;
use App\Traits\ResponseTrait;
use DateTimeZone;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
   use ResponseTrait;
    protected ICustomerRepositories $customerRepositories;
    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;
    protected IPatientRepositories $patientRepositories;
    protected ITherapistRepositories $therapistRepositories;
    protected ILocationRepositories $locationRepositories;
    protected SearchServiceProviderService $searchServiceProviderService;


    public function __construct(SearchServiceProviderService  $searchServiceProviderService , ICustomerRepositories $customerRepositories ,  ILocationRepositories $locationRepositories , ITherapistRepositories $therapistRepositories , IPatientRepositories $patientRepositories , IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
   {
       $this->customerRepositories = $customerRepositories;
       $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
       $this->patientRepositories = $patientRepositories;
       $this->therapistRepositories = $therapistRepositories;
       $this->locationRepositories = $locationRepositories;
       $this->searchServiceProviderService = $searchServiceProviderService;
   }

    public function getById($id): \Illuminate\Http\JsonResponse
   {
       try {
          $customer = $this->customerRepositories->findWith($id , ['location','patient','therapist' ,'therapist.specialty','rehabilitationCenter' ,'medicalSpecialties','schedules']);
         dd($customer);
          if(!$customer instanceof  Customer){
              throw new \Exception('Get Customer Failed');
          }
           return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new CustomerResource($customer), 201);
       }catch (\Exception $e){
           return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
       }

   }

    public function searchOfServiceProvider(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $results = $this->searchServiceProviderService->searchServiceProviders($request->all());
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),CustomerResource::collection($results), 202);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }

   }
   public function updateTimezone(UpdateTimeZoneRequest $timeZoneRequest): \Illuminate\Http\JsonResponse
   {
       try {
            $this->customerRepositories->update(['timezone' => $timeZoneRequest['timezone']] ,$timeZoneRequest['customer_id']);
           return $this->errorResponse(__('messages.UPDATE_SUCCESS'), [], 202);
       }catch (\Exception $e){
           return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
       }
   }
   public function getTimezone(): \Illuminate\Http\JsonResponse
   {
       try {
            return $this->errorResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), DateTimeZone::listIdentifiers(), 202);
       }catch (\Exception $e){
           return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
       }
   }

}
