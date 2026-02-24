<?php

namespace App\Http\Resources\Api\Program;

use App\Http\Resources\Api\Customer\AdminResource;
use App\Http\Resources\Api\Customer\RatingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
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
//                'service_provider' => new CustomerResource($this->whenLoaded('customer')),
                'creator' => new AdminResource($this->whenLoaded('creator')),
                'title' => $this->title_ar ,
                'description' => $this->description_ar ,
                'what_you_will_learn' =>$this->what_you_will_learn_ar ,
                'cover_image' => $this->cover_image ,
                'videos'=> VideoResource::collection($this->whenLoaded('videos')),
                'price' => $this->price ,
                'currency' => $this->currency,
                'status'=>$this->status ,
                'rating'=>RatingResource::collection($this->whenLoaded('ratings')),
                'is_approved'=> $this->is_approved ,
                'enrollments_count' => $this->enrollments_count,
                "ratings_avg_rating" => $this->ratings_avg_rating,
                "ratings_count" =>  $this->ratings_count ,
                '5_stars' => $this->ratings_4_to_5 ?? 0,
                '4_stars' => $this->ratings_3_to_4 ?? 0,
                '3_stars' => $this->ratings_2_to_3 ?? 0,
                '2_stars' => $this->ratings_1_to_2 ?? 0,
                '1_stars' => $this->ratings_0_to_1 ?? 0,
                'total_duration_minutes'=> $this->total_duration_minutes,
                'is_enrolled' => $this->is_enrolled,

             ] ;
    }
}
