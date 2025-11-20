<?php

namespace App\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsultationRequestedBroadcast implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $consultation , $message  , $eventType , $notification;

    public function __construct( $consultation, $message, string $eventType , $notification )
    {
        $this->consultation = $consultation;
        $this->message = $message;
        $this->eventType = $eventType;
        $this->notification = $notification;

        Log::info('bb executed for ConsultationRequested', [
            'consultation_id' => $consultation->id,
        ]);
    }


    public function broadcastOn()
    {
        try {
            $this->notification->update(['status' => 'sent']);
            $this->notification->save();
            if ($this->eventType === 'requested' || $this->eventType === 'cancelled_by_patient') {
                Log::info(' for nada', [
                    'requested' =>$this->consultation->consultant_id,
                ]);
                return new PrivateChannel('consultant.' . $this->consultation->consultant_id);
            }
            if ($this->eventType === 'accepted' || $this->eventType === 'cancelled_by_consultant') {
                Log::info(' for cancelled', [
                    'consultant' =>$this->consultation->consultant_id,
                ]);
                return new PrivateChannel('patient.' . $this->consultation->patient_id);
            }

            if($this->eventType === 'cancelled_by_system'  || $this->eventType === 'completed' || $this->eventType === 'reminder_for_all')
            {
                return [
                    new PrivateChannel('consultant.' . $this->consultation->consultant_id),
                    new PrivateChannel('patient.' . $this->consultation->patient_id),
                ];
            }

            return  throw new \Exception("Unknown eventType: " . $this->eventType);

        }catch (\Exception $e){
            $this->notification->update(['status' => 'failed']);
            $this->notification->save();

            throw $e;
        }


    }

    public function broadcastWith(): array
    {
        Log::info('cons video ' .$this->consultation );
        return [
            'id'              => $this->consultation->id,
            'patient_id'      => $this->consultation->patient_id,
            'patient_name'    => $this->consultation->patient->full_name,
            'consultant_id'   => $this->consultation->consultant_id,
            'consultant_name' => $this->consultation->consultant->full_name,
            'consultant_type' => $this->consultation->consultant_type,
            'video_room_link' => $this->consultation instanceof \App\Models\ConsultationVideoRequest ? $this->consultation->video_room_link : null,
            'message'         =>  $this->message,
        ];
    }
}
