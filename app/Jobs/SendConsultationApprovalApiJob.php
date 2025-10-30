<?php

namespace App\Jobs;

use App\Models\ConsultationVideoRequest;
use App\Services\api\ZoomMeetingService;
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
        $zoomService = new ZoomMeetingService(config('services.zoom.access_token'));
        try {
            $meetingData = $zoomService->createMeetingLinkZoom($this->dateTime, $this->duration, $this->consultation,);
            $this->consultation->update(['video_room_link' => $meetingData['join_url'] , 'zoom_meeting_id' => $meetingData['meeting_id']]);
        } catch (\Exception $e) {
            Log::error("Zoom Meeting creation failed: " . $e->getMessage());
        }
    }
}
