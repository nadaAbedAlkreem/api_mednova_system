<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentRequestFactory> */
    use HasFactory , softDeletes;
    protected $fillable = [
        'customer_id',
        'schedulable_id',
        'schedulable_type',
        'requested_time',
        'status',
        'description',
        'confirmed_end_time',
        'session_duration'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function schedulable()
    {
        return $this->morphTo();
    }
}
