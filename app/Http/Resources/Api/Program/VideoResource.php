<?php

namespace App\Http\Resources\Api\Program;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
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
                'title' => $this->title_ar ,
                'description' =>$this->description_ar ,
                'video_path'=>$this->video_path,
                'duration_minute' => $this->duration_minute ,
                'order' => $this->order ,
//                'status'=>$this->status ,
//                'is_preview'=>$this->is_preview ,
                'is_free'=>$this->is_free ,


            ] ;
    }
}
