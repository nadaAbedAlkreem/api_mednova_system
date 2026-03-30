<?php

namespace App\Http\Controllers\Api\Package;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserPackageRequest;
use App\Http\Requests\UpdateUserPackageRequest;
use App\Http\Resources\Api\ControlPanel\Subscribtion\UserPackageResource;
use App\Models\UserPackage;
use App\Repositories\IUserPackageRepositories;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserPackageController extends Controller
{
    use ResponseTrait;

    protected IUserPackageRepositories $userPackageRepositories;


    public function __construct(IUserPackageRepositories $userPackageRepositories)
    {
        $this->userPackageRepositories = $userPackageRepositories;
    }
     /**
     * Display a listing of the resource.
     */
    public function subscribedUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $limit = $request->query('limit') ?? 10;
            $today = Carbon::now();
            $subscribedUsers = $this->userPackageRepositories->paginateWhereWith(['is_active' => 1 ,['starts_at', '<=', $today], ['ends_at', '>=', $today]],['customer' , 'package'] , ['column' => 'id', 'dir' => 'DESC'] , $limit);
             return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), UserPackageResource::collection($subscribedUsers), 202 , [
                 'current_page' => $subscribedUsers->currentPage(),
                 'per_page' => $subscribedUsers->perPage(),
                 'total' => $subscribedUsers->total(),
                 'last_page' => $subscribedUsers->lastPage(),
                 'from' => $subscribedUsers->firstItem(),
                 'to' => $subscribedUsers->lastItem(),
                 'has_more_pages' => $subscribedUsers->hasMorePages()]);
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
    public function store(StoreUserPackageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserPackage $userPackage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserPackage $userPackage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserPackageRequest $request, UserPackage $userPackage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function accountDeactivation($id): \Illuminate\Http\JsonResponse
    {
        try {
            $userPackage = $this->userPackageRepositories->findOne($id);
            if (!$userPackage) {return $this->errorResponse(__('messages.PACKAGE_NOT_FOUND'), [], 404);}
            if (!$userPackage->is_active) {return $this->errorResponse(__('messages.pre_package_disabled'), [], 500);}
            $userPackage->update(['is_active' => 0, 'ends_at' => now()]);
            $userPackage->customer->update(['account_status' => AccountStatus::INACTIVE->value]);
            return $this->successResponse(__('messages.account_successfully_disabled'), [], 200);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}
