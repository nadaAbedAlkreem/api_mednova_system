<?php

namespace App\Jobs;

use App\Models\ConsultationVideoRequest;
use App\Services\Api\Consultation\ZoomMeetingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendConsultationApprovalApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $dateTime;
    protected int $duration;
    protected ConsultationVideoRequest $consultation;
    public $timeout = 45;     // Zoom API قد يأخذ وقت
    public $tries = 3;        // إعادة 3 مرات
    public $backoff = 30;     // انتظر 30 ثانية بين المحاولات

    /**
     * Create a new job instance.
     */
    public function __construct(string $dateTime, int $duration, ConsultationVideoRequest $consultation)
    {
        $this->dateTime = $dateTime;
        $this->duration = $duration;
        $this->consultation = $consultation;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $zoomService = new ZoomMeetingService();
        try {
            $meetingData = $zoomService->createMeetingLinkZoom($this->dateTime,$this->duration, $this->consultation);
            Log::info('zoom '. json_encode($meetingData));
            Log::info('consultation '. json_encode($this->consultation));
            $this->consultation->update(['video_room_link' => $meetingData['join_url'] , 'zoom_meeting_id' => $meetingData['meeting_id']]);
        } catch (\Exception $e) {
            Log::info("Zoom Meeting creation failed: " . $e->getMessage());
        }
    }
}
