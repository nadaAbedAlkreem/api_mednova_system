<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ConsultationVideoRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationVideoRequestFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable =
    [
      'appointment_request_id',
      'patient_id' ,
      'consultant_id',
      'consultant_type',
      'zoom_meeting_id',
      'status',
      'financial_status' ,
      'review_deadline' ,
      'released_at' ,
      'duration_minutes',
      'video_room_link',
      'action_by',
      'action_reason' ,
      'consultant_approved',
      'patient_approved',
      'session_duration_hours' ,
      'last_reminder_sent_at',
      'last_reminder_level' ,
      'consultation_price',
      'gateway_commission_rate',
      'gateway_commission_amount',
      'net_amount',
      'expires_at' ,
      'created_at',
      'updated_at',
    ];

    protected $dates = ['deleted_at'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'consultation_price'       => 'decimal:2',
        'gateway_commission_rate'  => 'decimal:2',
        'gateway_commission_amount'=> 'decimal:2',
        'net_amount'               => 'decimal:2',
    ];

//    protected $casts = [
//        'created_at' => 'datetime',
//        'updated_at' => 'datetime',
//    ];
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($consultationVideoRequest) {
            $consultationVideoRequest->activities()->each(function ($activities) {
                $activities->delete();
            });

        });
    }

    public function consultant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id');
    }

    public function appointmentRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AppointmentRequest::class, 'appointment_request_id');
    }
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationVideoActivity::class, 'consultation_video_request_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationVideoReport::class, 'consultation_video_request_id');
    }

}
