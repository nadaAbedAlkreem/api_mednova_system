<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory , SoftDeletes;
    protected $fillable = ['name_ar' ,'name_en' , 'description_ar' , 'description_en' , 'token' , 'stock'];
    public function deviceRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeviceRequest::class);
    }
}
