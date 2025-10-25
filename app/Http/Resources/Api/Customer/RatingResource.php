<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
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
                'reviewer' => new CustomerResource($this->whenLoaded('reviewer')),
                'reviewee' => new CustomerResource($this->whenLoaded('reviewee')),
                'reviewee_type' =>  $this->reviewee_type  ,
                'rating' => $this->rating ,
             ] ;
    }
}
