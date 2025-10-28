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
    protected int $durationMinutes; // وقت الفراغ بين كل جلسة

    public function __construct(int $duration = 60 , int $durationMinutes = 10)
    {
        $this->duration = $duration; // مدة كل جلسة بالدقائق
        $this->durationMinutes = $durationMinutes; // مدة كل جلسة بالدقائق
    }

    /**
     * الحصول على الفترات المتاحة لمستشار معين في يوم محدد
     */
    public function checkAvailableSlots(int $consultantId, string $consultantType, string $day, string $date , string $typeAppointment): array
    {
        $schedule = Schedule::where('consultant_id', $consultantId)
            ->where('is_active', true)
            ->where('consultant_type', $consultantType)
            ->firstOrFail();
         $availableSlots = $this->mergeAllSlots($schedule, $date);
         $bookedTimes = $this->getBookedTimes($consultantId, $day, $date , $typeAppointment);

        $freeSlots = $availableSlots->diff($bookedTimes)->values();

        return $freeSlots->toArray();
    }

    /**
     * دمج كل الفترات الزمنية (صباح + مساء)
     */
    protected function mergeAllSlots(Schedule $schedule, string $date)
    {
        $slots = collect();
         // المواعيد الصباحية
         $slots = $slots->merge($this->getSlotsForPeriod(
             Carbon::parse($schedule->start_time_morning)->format('H:i:s'),
             Carbon::parse($schedule->end_time_morning)->format('H:i:s'),
            $date
        ));


        // المواعيد المسائية
        if ($schedule->is_have_evening_time) {
            $slots = $slots->merge($this->getSlotsForPeriod(
                Carbon::parse($schedule->start_time_evening)->format('H:i:s'),
                Carbon::parse($schedule->end_time_evening)->format('H:i:s'),
                $date
            ));
        }

        return $slots;
    }

    /**
     * جلب الفترات الزمنية لفترة معينة
     */
    protected function getSlotsForPeriod(?string $start, ?string $end, string $date)
    {
        if (!$start || !$end) {
            return collect();
        }
         return collect($this->generateTimeSlots($start, $end, $this->duration, $date))
            ->map(fn($time) => $time);
    }

    /**
     * جلب المواعيد المحجوزة لنفس اليوم
     */
    protected function getBookedTimes(int $consultantId, string $day, string $date , string $typeAppointment)
    {
         return AppointmentRequest::where('consultant_id', $consultantId)
            ->where('requested_day', $day)
            ->whereDate('requested_time', $date)
            ->where('type_appointment' , $typeAppointment)
            ->where('status', 'pending')
            ->orWhere('status', 'approved')
            ->pluck('requested_time')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d H:i'));
    }

    /**
     * دالة توليد الفترات الزمنية بين وقت البداية والنهاية
     */
    protected function generateTimeSlots(string $start, string $end, int $duration, string $date): array
    {
        $slots = [];
        $startTime = Carbon::parse($date.' '.$start);
        $endTime = Carbon::parse($date.' '.$end);
         while ($startTime->lt($endTime))
         {
             $slots[] = $startTime->format('Y-m-d H:i');
             $startTime->addMinutes($duration)->addMinutes($this->durationMinutes);
         }
        return $slots;
    }




}
