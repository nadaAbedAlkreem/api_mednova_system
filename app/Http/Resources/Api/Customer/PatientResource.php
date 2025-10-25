<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
                'emergency_phone' => $this->emergency_phone ,
                'relationship' => $this->relationship ,
             ] ;
    }
}
