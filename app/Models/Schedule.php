<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleFactory> */
    use HasFactory , softDeletes;
    protected $fillable = [
        'schedulable_id' ,
        'schedulable_type',
        'day_of_week' ,
        'start_time_morning' ,
        'end_time_morning' ,
        'start_time_evening' ,
        'end_time_evening' ,
        'is_have_evening_time',
        'type' ,
        'is_active' ,
    ];
    public function schedulable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
