<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationVideoReport extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationVideoReportFactory> */
    use HasFactory , softDeletes;
    protected $fillable = [
        'consultation_video_request_id',
        'duration',
        'id',
        'join_time' ,
        'leave_time' ,
        'name',
        'user_email',
        'user_id'
    ];
    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function consultationVideoRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ConsultationVideoRequest::class, 'consultation_video_request_id');
    }


}
