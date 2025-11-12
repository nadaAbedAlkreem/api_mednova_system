<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlovePerformanceLog extends Model
{
    /** @use HasFactory<\Database\Factories\GlovePerformanceLogFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_id', 'glove_id', 'timestamp', 'flex_thumb', 'flex_index',
        'flex_middle', 'flex_ring', 'flex_pinky', 'resistance',
        'reaction_time', 'is_error', 'error_flag'
    ];

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveSession::class, 'session_id');
    }

    public function glove(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveDevice::class, 'glove_id');
    }
}
