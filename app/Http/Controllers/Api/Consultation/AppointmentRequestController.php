<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Consultation\ChackAvailableSlotsRequest;
use App\Http\Requests\StoreAppointmentRequestRequest;
use App\Http\Requests\UpdateAppointmentRequestRequest;
use App\Models\AppointmentRequest;
use App\Services\Api\Consultation\ConsultantAvailabilityService;
use App\Traits\ResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppointmentRequestController extends Controller
{
    use ResponseTrait;
    protected  ConsultantAvailabilityService $consultantAvailabilityService;
    public function __construct(ConsultantAvailabilityService $consultantAvailabilityService)
    {
        $this->consultantAvailabilityService = $consultantAvailabilityService;
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
    public function checkAvailableSlots(ChackAvailableSlotsRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
              $freeSlots = $this->consultantAvailabilityService->checkAvailableSlots(
                $request->patient_id ,
                $request->consultant_id,
                $request->consultant_type,
                $request->day ,
                $request->date ,
                $request->type_appointment,
                $request->timezone);
             return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ['day' =>$request->day ,'available_slots' => $freeSlots], 202,);

        }catch (ModelNotFoundException $e) {
             return $this->errorResponse(
                __('messages.ERROR_OCCURRED'),
                ['error' => 'لا يوجد مواعيد متاحة لهذا المختص في اليوم المحدد.'],
                404
            );
        }
        catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }

    }

    /**
     * 🔹 توليد فترات زمنية بين وقتي البداية والنهاية
     */


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequestRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AppointmentRequest $appointmentRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppointmentRequest $appointmentRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequestRequest $request, AppointmentRequest $appointmentRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppointmentRequest $appointmentRequest)
    {
        //
    }
}
