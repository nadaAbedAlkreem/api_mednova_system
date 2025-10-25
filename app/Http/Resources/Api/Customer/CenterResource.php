<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CenterResource extends JsonResource
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
                'year_establishment'=>$this->year_establishment ,
                'license_number'=>$this->license_number ,
                'license_authority' =>$this->license_authority,
                'license_file'=>$this->license_file ,
                'bio'=>$this->bio,
                'has_commercial_registration'=>$this-> has_commercial_registration,
                'commercial_registration_number'=>$this->commercial_registration_number,
                'commercial_registration_file'=>$this->commercial_registration_file ,
                'commercial_registration_authority'=>$this->commercial_registration_authority ,
             ] ;
    }
}
