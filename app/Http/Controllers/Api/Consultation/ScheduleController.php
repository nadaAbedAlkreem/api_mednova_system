<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreScheduleRequest;
use App\Http\Requests\api\user\UpdateScheduleRequest;
use App\Http\Resources\Api\Consultation\ScheduleResource;
use App\Models\Schedule;
use App\Repositories\IScheduleRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
     use ResponseTrait;
    protected IScheduleRepositories $scheduleRepositories;


    public function __construct(IScheduleRepositories $scheduleRepositories)
    {
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
//    public function store(StoreScheduleRequest $request)
//    {
//        try{
//            $rating = $this->scheduleRepositories->create($request->getData());
//            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ScheduleResource($rating), 201,);
//        }catch (\Exception $exception){
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500, app()->getLocale());
//        }
//    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateScheduleRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->scheduleRepositories->update($request->getData() ,$request['schedule_id'] );
            return $this->successResponse(__('messages.UPDATE_SUCCESS'), [], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        //
    }
}
