<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceRequestFactory> */
    use HasFactory , SoftDeletes;
    protected $fillable = ['customer_id' , 'device_id' , 'status' , 'request_date' , 'contact_status' , 'notes'];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

     public function device(): \Illuminate\Database\Eloquent\Relations\BelongsTo
     {
        return $this->belongsTo(Device::class);
    }
}
