<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapistResource extends JsonResource
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
                'medical_specialties' => new MedicalSpecialtyResource($this->whenLoaded('specialty')),
                'experience_years' =>$this->experience_years ,
                'university_name' =>$this->university_name ,
                'countries_certified'=>$this->countries_certified ,
                'graduation_year'=>$this->graduation_year ,
                'certificate_file'=>$this->certificate_file ,
                'license_number'=>$this->license_number ,
                'license_authority'=>$this->license_authority,
                'license_file'=>$this->license_file ,
                'bio'=>$this->bio ,


             ] ;
    }
}
