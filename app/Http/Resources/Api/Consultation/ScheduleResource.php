<?php

namespace App\Http\Resources\Api\Consultation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
           return
            [
                'id' => $this->id ,
                'day_of_week' =>json_decode($this->day_of_week),
                'start_time_morning' => $this->start_time_morning ? date('H:i', strtotime($this->start_time_morning)) : null,
                'end_time_morning'   => $this->end_time_morning ? date('H:i', strtotime($this->end_time_morning)) : null,
                'is_have_evening_time'=> $this->is_have_evening_time,
                'start_time_evening' => $this->start_time_evening ? date('H:i', strtotime($this->start_time_evening)) : null,
                'end_time_evening'   => $this->end_time_evening ? date('H:i', strtotime($this->end_time_evening)) : null,
                'type_time' => $this->type


             ] ;
    }
}
