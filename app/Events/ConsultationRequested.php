<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsultationRequested
{
    use Dispatchable, SerializesModels;

    public $consultation , $message  , $eventType;

    /**
     * Create a new event instance.
     */
    public function __construct( $consultation , $message , $eventType )
    {
        $this->consultation = $consultation->load(['patient', 'consultant']);
        $this->message = $message;
        $this->eventType = $eventType;
        Log::info('Listener executed for ConsultationRequested', [
            'consultation_id' => $consultation->id,
          ]);
    }

}
