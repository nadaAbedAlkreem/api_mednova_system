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
      'customer_id' ,
      'consultant_id',
      'consultant_type',
      'health_status',
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

    public function consultant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }


}
