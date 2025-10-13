<?php

namespace App\Http\Resources;

use App\Models\MedicalSpecialtie;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
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
                'image' =>  ($this->image)  ,
                'full_name' => $this->full_name,
                'email' => $this->email,
                'phone' => $this->phone ,
             ] ;
    }
}
