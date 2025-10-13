<?php

namespace App\Http\Resources;

use App\Models\MedicalSpecialtie;
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
                'cover_image' => $this->cover_image ,
                'videos'=> VideoResource::collection($this->whenLoaded('videos')),
                'price' => $this->price ,
                'status'=>$this->status ,
                'is_approved'=> $this->is_approved ,
             ] ;
    }
}
