<?php

namespace App\Http\Resources\Api\ControlPanel\Subscribtion;

use App\Http\Resources\Api\Customer\AdminResource;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Customer\RatingResource;
use App\Http\Resources\Api\Program\VideoResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
          'name' => $this->name_ar,
          'description' => $this->description_ar ,
          'type' => $this->type ,
          'price' => $this->price ,
          'billing_cycle' => $this->billing_cycle ,
          'is_active' => $this->is_active
        ];
    }
}
