<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\AppointmentRequest;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;
use Illuminate\Support\Carbon;

class ConsultantAvailabilityService
{
    protected int $duration;

    public function __construct(int $duration = 60)
    {
        $this->duration = $duration; // مدة كل جلسة بالدقائق
    }

    /**
     * الحصول على الفترات المتاحة لمستشار معين في يوم محدد
     */
    public function checkAvailableSlots(int $consultantId, string $consultantType, string $day): array
    {

        $schedule = Schedule::where('consultant_id', $consultantId)
            ->where('is_active', true)
            ->where('consultant_type', $consultantType)
            ->firstOrFail();

        $availableSlots = $this->mergeAllSlots($schedule); // هنا يجب مراعاة بعض الحالات قد يكون المستشار ليس لديه مواعيد في نظام

        $bookedTimes = $this->getBookedTimes($consultantId, $day);
        $freeSlots = $availableSlots->diff($bookedTimes)->values();

        return $freeSlots->toArray();
    }

    /**
     * دمج كل الفترات الزمنية (صباح + مساء)
     */
    protected function mergeAllSlots(Schedule $schedule)
    {
        $slots = collect();

        $slots = $slots->merge($this->getSlotsForPeriod($schedule->start_time_morning, $schedule->end_time_morning));

        if ($schedule->is_have_evening_time) {
            $slots = $slots->merge($this->getSlotsForPeriod($schedule->start_time_evening, $schedule->end_time_evening));
        }

        return $slots;
    }

    /**
     * جلب الفترات الزمنية لفترة معينة
     */
    protected function getSlotsForPeriod(?string $start, ?string $end)
    {
        if (!$start || !$end) {
            return collect();
        }

        return collect($this->generateTimeSlots($start, $end, $this->duration));
    }

    /**
     * جلب المواعيد المحجوزة لنفس اليوم
     */
    protected function getBookedTimes(int $consultantId, string $day)
    {
        return AppointmentRequest::where('consultant_id', $consultantId)
            ->where('requested_day', $day)
            ->pluck('requested_time')
            ->map(fn($t) => Carbon::parse($t)->format('H:i'));
    }

    /**
     * دالة توليد الفترات الزمنية بين وقت البداية والنهاية
     */
    protected function generateTimeSlots(string $start, string $end, int $duration): array
    {
        $startTime = Carbon::parse($start);
        $endTime = Carbon::parse($end);

        $slots = [];
        while ($startTime->lt($endTime)) {
            $slots[] = $startTime->format('H:i');
            $startTime->addMinutes($duration);
        }

        return $slots;
    }

}
