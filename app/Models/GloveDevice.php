<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GloveDevice extends Model
{
    /** @use HasFactory<\Database\Factories\GloveDeviceFactory> */
    use HasFactory ,SoftDeletes;
    const STATUS_CONNECTED = 'connected';
    const STATUS_ACTIVE = 'active';
    const STATUS_PROGRESS = 'pairing_in_progress';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_ERROR = 'error';
    protected $fillable = [
        'smart_glove_id', 'device_id', 'serial_number', 'customer_id', 'status', 'last_seen_at'
    ];
    public static $statusLabels = [
        self::STATUS_CONNECTED => 'connected',
        self::STATUS_PROGRESS => 'pairing_in_progress',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_DISCONNECTED => 'disconnected',
        self::STATUS_ERROR => 'error',
    ];

    public function device(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function data(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GloveData::class, 'glove_id');
    }

    public function errors()
    {
        return $this->hasMany(GloveError::class, 'glove_id');
    }

    public function commands()
    {
        return $this->hasMany(GloveCommand::class, 'glove_id');
    }

    public function sessions()
    {
        return $this->hasMany(GloveSession::class, 'glove_id');
    }
}
