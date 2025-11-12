<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GloveCommand extends Model
{
    /** @use HasFactory<\Database\Factories\GloveCommandFactory> */
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'glove_id','command_code', 'param1','session_id', 'rep_index', 'param2', 'speed', 'ack_status_send', 'sent_at', 'ack_received_send_at' , 'ack_status_device_response' ,'ack_received_device_response_at' ,
    ];

    public function glove(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveDevice::class, 'glove_id');
    }
    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveSession::class, 'session_id');
    }
    public function gloveError(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GloveError::class, 'glove_id');
    }
}
