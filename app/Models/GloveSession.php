<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GloveSession extends Model
{
    /** @use HasFactory<\Database\Factories\GloveSessionFactory> */
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'glove_id','status', 'exercise_type', 'repetitions_target', 'repetitions_done',
        'avg_reaction_time','default_speed' ,'interval_between_reps','avg_resistance', 'success_rate', 'session_start', 'session_end'
    ];

    public function glove(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveDevice::class, 'glove_id');
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlovePerformanceLog::class, 'session_id');
    }

    public function commands(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GloveCommand::class, 'session_id');
    }
}
