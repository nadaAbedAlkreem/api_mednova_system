<?php

namespace App\Http\Resources\Api\Customer;

use App\Enums\ConsultantType;
use App\Http\Resources\Api\Consultation\ScheduleResource;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = $this->timezone ;
        if($timezone != null)
        {
            $schedules = $this->whenLoaded('schedules', function () use ($timezone) {
                return $this->schedules->map(function ($schedule) use ($timezone) {
                    $schedule->timezone = $timezone;
                    return $schedule;
                });
            });

        }else{
            $schedules = $this->whenLoaded('schedules');
        }
          return
            [
                'id' => $this->id ,
                'image' =>  ($this->image)  ,
                'full_name' => $this->full_name,
                'email' => $this->email,
                'phone' => $this->phone ,
                'type_account' => $this->type_account ,
                'birth_date' => $this->birth_date,
                'gender' => $this->gender,
                'patient_details' => new PatientResource($this->whenLoaded('patient')),
                'location_details' => new LocationResource($this->whenLoaded('location')),
                'therapist_details' => new TherapistResource($this->whenLoaded('therapist')),
                'center_details' => new CenterResource($this->whenLoaded('rehabilitationCenter')),
                'medicalSpecialties' => MedicalSpecialtyResource::collection($this->whenLoaded('medicalSpecialties')),
                'schedules' => ScheduleResource::collection($schedules),
                'average_rating' => $this->average_rating ,
                'total_reviews'=> $this->total_reviews   ,
                'is_completed' => $this->isProfileCompleted(),
                'approval_status' => $this->approval_status ,
                'is_banned' => $this->is_banned ,
                'timezone'=> $this->timezone ?? app('config')->get('app.timezone') ,
                'email_verified_at'=> $this->email_verified_at,
                'phone_verified_at' => $this->phone_verified_at


            ] ;
    }


}
