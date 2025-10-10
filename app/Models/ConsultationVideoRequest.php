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
      'customer_id' ,
      'consultation_id',
      'consultation_type',
      'status',
      'schedule_id' ,
      'duration_minutes',
      'video_room_link',
      'start_time',
      'end_time',
    ];
}
