<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
                'latitude' =>  $this->latitude ,
                'longitude' => $this->longitude ,
                'formatted_address' =>$this->formatted_address ,
                'country' => $this->country ,
                'region'=>$this->region ,
                'city' =>$this->city ,
                'district' =>$this->district ,
                'postal_code' => $this->postal_code ,
                'location_type'=> $this->location_type


             ] ;
    }
}
