<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Device\DeviceResource;
use App\Models\Device;
use App\Traits\ResponseTrait;

class DeviceController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function get()
    {
        try {
            $device = Device::all();
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),  DeviceResource::collection($device), 202);
        }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
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
    public function store(StoreDeviceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Device $device)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceRequest $request, Device $device)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        //
    }
}
