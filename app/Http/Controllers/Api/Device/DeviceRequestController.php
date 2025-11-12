<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\device\StoreDeviceRequestRequest;
use App\Http\Requests\UpdateDeviceRequestRequest;
use App\Http\Resources\Api\Device\DeviceRequestResource;
use App\Models\DeviceRequest;
use App\Repositories\IDeviceRequestRepositories;
use App\Traits\ResponseTrait;

class DeviceRequestController extends Controller
{
    use ResponseTrait;
    protected  IDeviceRequestRepositories $deviceRequestRepository;
    public function __construct(IDeviceRequestRepositories $deviceRequestRepository)
    {
        $this->deviceRequestRepository = $deviceRequestRepository;
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
    public function store(StoreDeviceRequestRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $deviceRequest = $this->deviceRequestRepository->create($request->getData());
            $deviceRequest->load(['customer','device']);
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new DeviceRequestResource($deviceRequest), 201,);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DeviceRequest $deviceRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeviceRequest $deviceRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceRequestRequest $request, DeviceRequest $deviceRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeviceRequest $deviceRequest)
    {
        //
    }
}
