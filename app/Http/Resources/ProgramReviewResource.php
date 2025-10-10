<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramReviewResource extends JsonResource
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
                'program'=> new ProgramResource($this->whenLoaded('program')),
                'customer'=> new CustomerResource($this->whenLoaded('customer')),
             ] ;
    }
}
