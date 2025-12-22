<?php

namespace App\Http\Controllers\Api\Customer;

use App\Events\MessageRead;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Consultation\NotificationsResource;
use App\Models\Notification;
use App\Repositories\INotificationRepositories;
use App\Traits\ResponseTrait;
use Exception;
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
    public function getNotificationsForCurrentUser(Request $request): \Illuminate\Http\JsonResponse
    {
         try {
            $customer = auth('api')->user();
            $limit = $request->get('limit', config('app.pagination_limit')) ?? 10;
            $cursor = $request->query('next_cursor'); // هذا الـ cursor الجديد
             if(!$customer) {throw new \Exception('Get Current User  Failed');}
//            $notifications = $this->notificationRepositories->cursorPaginateWhereWith(['notifiable_id' => $customer->id] , ['notifiable'] , ['column' => 'id', 'dir' => 'DESC'] , $limit);
//            $nextCursor = $notifications->nextCursor()?->encode();
             $notificationsQuery = Notification::where('notifiable_id',  $customer->id)
                 ->with(['notifiable'])
                 ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
              dd($notificationsQuery);
             // إذا كان هناك cursor موجود، استخدمه
             $notifications = $notificationsQuery->cursorPaginate($limit, ['*'], 'cursor', $cursor);
             $nextCursor = $notifications->nextCursor()?->encode();
             return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ['notification' =>NotificationsResource::collection($notifications) ,'next_cursor' => $nextCursor], 200);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function markAsRead(): \Illuminate\Http\JsonResponse
    {
        try{
            $currentUserID = auth('api')->id();
            if (!$currentUserID) {
                throw new \Exception('current not found');
            }
            $updated = Notification::where('notifiable_id', $currentUserID)
                ->where('read_at', null)
                ->update(['read_at' => now()]);
            if($updated){ event(new MessageRead($currentUserID,  $currentUserID));}
            return $this->successResponse(__('messages.UPDATE_SUCCESS'));
        }catch (Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()]);
        }
    }
}
