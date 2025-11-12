<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GloveError extends Model
{
    /** @use HasFactory<\Database\Factories\GloveErrorFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'glove_id','command_id', 'error_flag' ,'error_type','error_message' , 'repeat_count', 'first_occurrence', 'last_occurrence', 'acknowledged'
    ];

    const PYTHON_UNREACHABLE = 'python_unreachable';
    const COMMAND_TIMEOUT = 'command_timeout';
    const INVALID_ACK = 'invalid_ack';
    const CRC_ERROR = 'crc_error';
    const UNKNOWN = 'unknown';

    public static array $statusLabels = [
        self::PYTHON_UNREACHABLE ,
        self::COMMAND_TIMEOUT,
        self::INVALID_ACK,
        self::CRC_ERROR ,
        self::UNKNOWN ,
    ];

    public function glove(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveDevice::class, 'glove_id');
    }
    public function command(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveCommand::class, 'command_id');
    }

}
