<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationsRequest;
use App\Http\Requests\UpdateNotificationsRequest;
use App\Http\Resources\NotificationsResource;
use App\Models\Customer;
use App\Models\Notification;
use App\Repositories\INotificationRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{

    use ResponseTrait ;
    protected INotificationRepositories $notificationRepositories;

    public function __construct(INotificationRepositories $notificationRepositories)
    {
        $this->notificationRepositories = $notificationRepositories;

    }

    /**
     * Display a listing of the resource.
     */
    public function getNotificationsForCurrentUser(Request $request ): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $request->get('limit', config('app.pagination_limit'));
            if(!$user)
            {throw new \Exception('Get Current User  Failed');}
            $notifications = $this->notificationRepositories->paginateWhereWith(['notifiable_id' => $user->id] , ['notifiable'] , ['column' => 'id', 'dir' => 'DESC'] , $limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), NotificationsResource::collection($notifications), 200);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
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
    public function store(StoreNotificationsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notifications)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Notification $notifications)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificationsRequest $request, Notification $notifications)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notifications)
    {
        //
    }
}
