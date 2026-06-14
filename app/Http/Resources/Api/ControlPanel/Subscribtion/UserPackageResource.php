<?php

namespace App\Http\Resources\Api\ControlPanel\Subscribtion;

use App\Http\Resources\Api\Customer\AdminResource;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Customer\RatingResource;
use App\Http\Resources\Api\Program\VideoResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPackageResource extends JsonResource
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
                 'subscriber' => new CustomerResource($this->whenLoaded('customer')),
                 'package' => new  PackageResource($this->whenLoaded('package')),
                 'starts_at' => $this->starts_at ,
                 'ends_at' => $this->ends_at ,
                 'is_active' => $this->is_active ,

        ];
    }
}
