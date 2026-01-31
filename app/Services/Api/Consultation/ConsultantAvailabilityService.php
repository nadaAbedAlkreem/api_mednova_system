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
    protected int $durationMinutes;
    protected ICustomerRepositories $customerRepositories;
    protected TimezoneService $timezoneService;

    public function __construct(
        TimezoneService $timezoneService,
        ICustomerRepositories $customerRepositories,
        int $duration = 60,
        int $durationMinutes = 10
    ) {
        $this->duration = $duration;
        $this->durationMinutes = $durationMinutes;
        $this->customerRepositories = $customerRepositories;
        $this->timezoneService = $timezoneService;
    }

    /**
     * تحديد التايم زون (مريض → request → افتراضي)
     */
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
     * الحصول على المواعيد المتاحة
     */
    public function checkAvailableSlots(
        ?int $patientId,
        int $consultantId,
        string $consultantType,
        string $day,
        string $date,
        string $typeAppointment,
        ?string $timezone = null
    ): array {
        $userTimezone = $this->resolveTimezone($patientId, $timezone);

        $schedule = Schedule::where('consultant_id', $consultantId)
            ->where('consultant_type', $consultantType)
            ->where('is_active', true)
            ->firstOrFail();

        $availableSlots = $this->mergeAllSlots($schedule, $date, $userTimezone);

        $bookedTimes = $this->getBookedTimes(
            $consultantId,
            $day,
            $date,
            $typeAppointment,
            $userTimezone
        );

        return $availableSlots
            ->diff($bookedTimes)
            ->values()
            ->toArray();
    }

    /**
     * دمج الفترات الصباحية + المسائية
     */
    protected function mergeAllSlots(
        Schedule $schedule,
        string $date,
        string $timezone
    ) {
        $slots = collect();

        $slots = $slots->merge(
            $this->getSlotsForPeriod(
                $schedule->start_time_morning,
                $schedule->end_time_morning,
                $date,
                $timezone
            )
        );

        if ($schedule->is_have_evening_time) {
            $slots = $slots->merge(
                $this->getSlotsForPeriod(
                    $schedule->start_time_evening,
                    $schedule->end_time_evening,
                    $date,
                    $timezone
                )
            );
        }

        return $slots;
    }

    /**
     * فترات زمنية لفترة واحدة
     */
    protected function getSlotsForPeriod(
        ?string $start,
        ?string $end,
        string $date,
        string $timezone
    ) {
        if (!$start || !$end) {
            return collect();
        }

        return collect(
            $this->generateTimeSlots($start, $end, $date, $timezone)
        );
    }

    /**
     * المواعيد المحجوزة
     */
    protected function getBookedTimes(
        int $consultantId,
        string $day,
        string $date,
        string $typeAppointment,
        string $timezone
    ) {
        return AppointmentRequest::where('consultant_id', $consultantId)
            ->where('requested_day', $day)
            ->whereDate('requested_time', $date)
            ->where('type_appointment', $typeAppointment)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('requested_time')
            ->map(fn ($time) => Carbon::parse($time)
                ->timezone($timezone)
                ->format('Y-m-d H:i')
            );
    }

    /**
     * توليد الفترات الزمنية
     */
    protected function generateTimeSlots(
        string $start,
        string $end,
        string $date,
        string $timezone
    ): array {
        $slots = [];

        $startTime = Carbon::parse($date . ' ' . $start);
        $endTime   = Carbon::parse($date . ' ' . $end);
        $now       = Carbon::now($timezone);

        while ($startTime->lt($endTime)) {
            $slotEnd = $startTime->copy()->addMinutes($this->duration);

            if ($slotEnd->gt($endTime)) {
                break;
            }

            $slotForUser = $this->timezoneService
                ->toUserTimezone($startTime, $timezone);

            if (!($slotForUser->isToday() && $slotForUser->lt($now))) {
                $slots[] = $slotForUser->format('Y-m-d H:i');
            }

            $startTime->addMinutes($this->duration + $this->durationMinutes);
        }

        return $slots;
    }
}
