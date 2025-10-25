<?php

namespace App\Http\Resources\Api\Customer;

use App\Http\Resources\Api\Program\ProgramResource;
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
        $resource = ($this->reviewee_type == 'App\Models\Customer')? new CustomerResource($this->whenLoaded('reviewee')) :new ProgramResource($this->whenLoaded('reviewee')) ;
           return
            [
                'id' => $this->id ,
                'reviewer' => new CustomerResource($this->whenLoaded('reviewer')),
                'reviewee' => $resource,
                'reviewee_type' =>  $this->reviewee_type  ,
                'rating' => $this->rating ,
             ] ;
    }
}
