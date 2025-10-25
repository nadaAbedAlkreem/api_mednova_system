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
        'consultant_id' ,
        'consultant_type' ,
        'day_of_week' ,
        'start_time_morning' ,
        'end_time_morning' ,
        'is_have_evening_time',
        'start_time_evening' ,
        'end_time_evening' ,
        'type' ,
        'is_active' ,
    ];
    protected $casts = [
        'start_time_morning' => 'datetime',
        'end_time_morning' => 'datetime',
        'start_time_evening' => 'datetime',
        'end_time_evening' => 'datetime',
        'is_have_evening_time' => 'boolean',
    ];

    public function consultant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }
}
