<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\consultation\StoreActiveChatting;
use App\Http\Requests\api\consultation\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\Api\Consultation\MessageResource;
use App\Http\Resources\Api\Consultation\MessengersResource;
use App\Jobs\BroadcastMessageJob;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\Message;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ResponseTrait ;
    public function fetchMessages($chatRequestId, Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();
            $limit = $request->query('limit') ?? 20;

            if (!$user instanceof Customer) {
                throw new \Exception('Get Current User Failed');
            }
            if (!$chatRequestId instanceof ConsultationChatRequest ) {
                throw new \Exception('Get Current User Failed');
            }
            $chat = ConsultationChatRequest::where(function ($q) use ($user) {
                $q->where('patient_id', $user->id)
                    ->orWhere('consultant_id', $user->id);
            })->findOrFail($chatRequestId);

            $messages = Message::where('chat_request_id', $chatRequestId)
                ->with(['sender', 'receiver'])
                ->orderBy('created_at', 'asc')
                ->paginate($limit);

            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), MessageResource::collection($messages), 200);
        }catch (Exception $exception)
        {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function sendMessage(StoreMessageRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
             $message = Message::create($request->getData());
             BroadcastMessageJob::dispatch($message);
             $message->load(['sender','receiver']);
              return $this->successResponse(__('messages.SEND'),new MessageResource($message), 200);
        }catch (Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
    public function getMessengers(Request $request): \Illuminate\Http\JsonResponse
    {
        try{
            $customer = auth()->user();
            $limit = $request->query('limit') ?? 20;
            if(!$customer instanceof Customer){
                throw new \Exception('Get Current User  Failed');
            }
            $currentTypeCustomer = ($customer->type_account == 'patient')? 'patient_id' : 'consultant_id';
            $messengers = ConsultationChatRequest::with(['patient','consultant'])->withCount(['messages as unread_messages_count' => function($query) use ($customer) {
                $query->where('is_read', false)
                    ->where('receiver_id', $customer->id);
            }])->where($currentTypeCustomer , $customer->id)->where('status','accepted')->orderBy('created_at', 'desc')->paginate($limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), MessengersResource::collection($messengers), 200);
        }catch (Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function markAsRead($senderId): \Illuminate\Http\JsonResponse
    {
        try{
          if (!Customer::find($senderId)) {
                throw new \Exception('sender not found');
          }
          Message::where('sender_id', $senderId)
              ->where('receiver_id', auth()->id())
              ->where('is_read', false)
              ->update(['is_read' => true]);
           return $this->successResponse(__('messages.UPDATE_SUCCESS'));
        }catch (Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()]);
        }
    }
    /**
     * Store a newly created resource in storage.
     */

    public function store(StoreMessageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Message $message)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageRequest $request, Message $message)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        //
    }
}
