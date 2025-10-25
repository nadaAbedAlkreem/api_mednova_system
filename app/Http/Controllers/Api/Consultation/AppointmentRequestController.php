<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequestRequest;
use App\Http\Requests\UpdateAppointmentRequestRequest;
use App\Models\AppointmentRequest;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'center_id' => 'required|integer|exists:customers,id',
            'day' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday', //
        ]);

        $centerId = $request->center_id;
        $day = strtolower($request->day);
        $schedule = Schedule::where('schedulable_id', $centerId)
            ->where('schedulable_type', 'App\\Models\\Customer')
            ->where('is_active', true)
            ->firstOrFail();

        $duration = 60;
        $availableSlots = [];
        if ($schedule->start_time_morning && $schedule->end_time_morning) {
            $availableSlots = array_merge(
                $availableSlots,
                $this->generateTimeSlots(
                    $schedule->start_time_morning,
                    $schedule->end_time_morning,
                    $duration
                )
            );
        }
        if ($schedule->is_have_evening_time && $schedule->start_time_evening && $schedule->end_time_evening) {
            $availableSlots = array_merge(
                $availableSlots,
                $this->generateTimeSlots(
                    $schedule->start_time_evening,
                    $schedule->end_time_evening,
                    $duration
                )
            );
        }
        $bookedTimes = AppointmentRequest::where('service_provider_id', $centerId)
            ->where('requested_day', $day)
            ->pluck('requested_time')
            ->map(fn($t) => Carbon::parse($t)->format('H:i'))
            ->toArray();
        $freeSlots = array_values(array_diff($availableSlots, $bookedTimes));

        return response()->json([
            'day' => $day,
            'available_slots' => $freeSlots,
        ]);
    }

    /**
     * 🔹 توليد فترات زمنية بين وقتي البداية والنهاية
     */
    private function generateTimeSlots($startTime, $endTime, $duration): array
    {
        $slots = [];
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        while ($start->lt($end)) {
            $slots[] = $start->format('H:i');
            $start->addMinutes($duration);
        }

        return $slots;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequestRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AppointmentRequest $appointmentRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppointmentRequest $appointmentRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequestRequest $request, AppointmentRequest $appointmentRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppointmentRequest $appointmentRequest)
    {
        //
    }
}
