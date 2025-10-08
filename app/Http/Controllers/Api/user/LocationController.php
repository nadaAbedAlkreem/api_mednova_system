<?php

namespace App\Http\Controllers\Api\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreLocationRequest;
use App\Http\Requests\api\user\UpdateLocationRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Models\Patient;
use App\Repositories\ILocationRepositories;
use App\Traits\ResponseTrait;

class LocationController extends Controller
{
    use ResponseTrait;
    protected ILocationRepositories $locationRepositories;
    public function __construct(ILocationRepositories $locationRepositories)
    {
        $this->locationRepositories = $locationRepositories;
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
    public function store(StoreLocationRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $location = $this->locationRepositories->create($request->validated());
            if(!$location instanceof Location){
                throw new \Exception('Create Location  Failed');
            }
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new LocationResource($location), 201,);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500, app()->getLocale());

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request)
    {
        try{
            $data = $request->validated();
            $dataWithoutType = collect($data)->except('user_id')->toArray();
            $this->locationRepositories->updateWhere($dataWithoutType, ['customer_id' => $request->validated()['customer_id']] ) ;
            return $this->successResponse(__('messages.UPDATE_SUCCESS'),[], 202);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500, app()->getLocale());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        //
    }
}
