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
        'consultant_id',
        'patient_id',
        'consultant_type',
        'requested_day',
        'requested_time',
        'timezone',
        'status',
        'type_appointment',
        'confirmed_end_time',
        'session_duration' ,
        'is_finished' ,
        'finished_at',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'requested_time' => 'datetime',
        'confirmed_end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',

    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class , 'patient_id');
    }
    public function consultant()
    {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }
    public function consultationVideoRequest()
    {
        return $this->hasOne(ConsultationVideoRequest::class, 'appointment_request_id');
    }




}
