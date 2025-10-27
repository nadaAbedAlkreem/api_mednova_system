<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationVideoRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationVideoRequestFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
      'appointment_request_id',
      'patient_id' ,
      'consultant_id',
      'consultant_type',
      'status',
      'duration_minutes',
      'video_room_link',
      'action_by',
      'action_reason' ,
      'session_duration_hours' ,
      'last_reminder_sent_at',
      'last_reminder_level' ,
      'expires_at' ,
    ];
    protected $dates = ['deleted_at'];

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
    public function activities()
    {
        return $this->hasMany(ConsultationVideoActivity::class, 'consultation_video_request_id');
    }

}
