<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationVideoActivity extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationVideoActivitiveFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'consultation_video_activities';

    protected $fillable = [
        'consultation_video_request_id',
        'invitee_id',
        'role',
        'joined_at',
        'left_at',
        'duration_minutes',
//        'last_reminder_sent_at',
//        'last_reminder_level',
        'joined_method',
        'ip_address',
        'device',
        'data_center',
        'status',
        'notes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

     public function consultationVideoRequest()
    {
        return $this->belongsTo(ConsultationVideoRequest::class, 'consultation_video_request_id');
    }

    public function invitee()
    {
        return $this->belongsTo(Customer::class, 'invitee_id');
    }
}
