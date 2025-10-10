<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationVideoRescheduleRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationVideoRescheduleRequestFactory> */
    use HasFactory , SoftDeletes;
    protected $fillable = [
        'consultation_video_request_id' ,
        'requester_id',
        'requester_type' ,
        'status' ,
        'reason' ,
        'old_start_time',
        'old_end_time',
        'new_start_time',
        'new_end_time',
        'action_by' ,
        'action_note'
    ];
}
