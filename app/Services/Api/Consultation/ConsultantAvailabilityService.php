<?php
namespace App\Services\Api\Consultation;

use App\Models\AppointmentRequest;
use App\Models\Schedule;
use App\Repositories\ICustomerRepositories;
use App\Services\Api\Customer\TimezoneService;
use Illuminate\Support\Carbon;

class ConsultantAvailabilityService
{
    protected int $duration;
    protected int $durationMinutes; // وقت الفراغ بين كل جلسة
    protected int $patientId ;
    protected ICustomerRepositories $customerRepositories;
    protected TimezoneService $timezone;

    public function __construct(TimezoneService $timezone , ICustomerRepositories $customerRepositories ,int $duration = 60 , int $durationMinutes = 10 , int $patientId = 0 )
    {
        $this->duration = $duration; // مدة كل جلسة بالدقائق
        $this->durationMinutes = $durationMinutes; // مدة كل جلسة بالدقائق
        $this->patientId = $patientId;
        $this->customerRepositories = $customerRepositories;
        $this->timezone = $timezone;
    }
    private function resolveTimezone(?int $patientId, ?string $timezone): string
    {
        if ($patientId) {
            $patient = $this->customerRepositories->findOne($patientId);
            if ($patient && $patient->timezone) {
                return $patient->timezone;
            }
        }

        if ($timezone) {
            return $timezone;
        }

        return config('app.timezone');
    }


    /**
     * الحصول على الفترات المتاحة لمستشار معين في يوم محدد
     */
    public function checkAvailableSlots(?int $patientId, int $consultantId, string $consultantType, string $day, string $date , string $typeAppointment ,     ?string $timezone = null
    ): array
    {
        $this->patientId = $patientId;
        $schedule = Schedule::where('consultant_id', $consultantId)
            ->where('is_active', true)
            ->where('consultant_type', $consultantType)
            ->firstOrFail();
        $availableSlots = $this->mergeAllSlots($schedule, $date);
        $bookedTimes = $this->getBookedTimes($consultantId, $day, $date , $typeAppointment);
        $freeSlotsUtc = $availableSlots->diff($bookedTimes)->values();
//        dd($freeSlotsUtc);
//        $patient = $this->customerRepositories->findOrFail($patientId);
//        $patientTimezone = $patient->timezone ?? config('app.timezone');
//        $now = Carbon::now($patientTimezone);
//
//        $slotsForPatient = $freeSlotsUtc->map(function ($slotUtc) use ($patientTimezone, $now) {
//            $slotLocal = $this->timezone->toUserTimezone(Carbon::parse($slotUtc), $patientTimezone, 'Y-m-d H:i');
////             dd($slotLocal);
//             return $slotLocal;
//            // استبعاد الأوقات الماضية
////            return Carbon::parse($slotLocal)->gt($now) ? $slotLocal : null;
//        })->filter()->values();
//        dd($slotsForPatient->toArray());

        return $freeSlotsUtc->toArray();
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
        $patient = $this->customerRepositories->findOrFail($this->patientId);
        $patientTimezone = $patient->timezone ?? config('app.timezone'); // لو ما فيش timezone خذ الافتراضي

        return AppointmentRequest::where('consultant_id', $consultantId)
            ->where('requested_day', $day)
            ->whereDate('requested_time', $date)
            ->where('type_appointment', $typeAppointment)
            ->where(function ($q) {
                $q->where('status', 'pending')
                    ->orWhere('status', 'approved');
            })
            ->pluck('requested_time')
            ->map(fn($t) => Carbon::parse($t)
                ->timezone($patientTimezone)  // تحويل UTC -> توقيت المريض
                ->format('Y-m-d H:i')
            );
    }

    /**
     * دالة توليد الفترات الزمنية بين وقت البداية والنهاية
     */
    protected function generateTimeSlots(string $start, string $end, int $duration, string $date): array
    {
        $slots = [];
        $startTime = Carbon::parse($date.' '.$start);
        $endTime = Carbon::parse($date.' '.$end);
        $patient = $this->customerRepositories->findOrFail($this->patientId);
        $patientTimezone = $patient->timezone ?? null;
        $now = Carbon::now($patientTimezone);
        while ($startTime->lt($endTime)) {
//            $slotForPatient = $startTime->copy()->setTimezone($patientTimezone);
            $slotEnd = $startTime->copy()->addMinutes($duration); // نهاية الموعد
            if ($slotEnd->gt($endTime)) {
                break; // أوقف التوليد
            }
            $slotForPatient = $this->timezone->toUserTimezone($startTime ,$patientTimezone );
            if (!($slotForPatient->toDateString() === $now->toDateString() && $slotForPatient->lt($now))) {
                $slots[] = $slotForPatient->format('Y-m-d H:i');
            }
            $startTime->addMinutes($duration)->addMinutes($this->durationMinutes);
        }
        return $slots;
    }
//    protected function generateTimeSlots(string $start, string $end, int $duration, string $date): array
//    {
//        $slots = [];
//        $startTime = Carbon::parse($date.' '.$start);
//        $endTime = Carbon::parse($date.' '.$end);
//        while ($startTime->lt($endTime))
//        {
//            $slots[] = $startTime->format('Y-m-d H:i');
//            $startTime->addMinutes($duration)->addMinutes($this->durationMinutes);
//        }
//        return $slots;
//    }






}
