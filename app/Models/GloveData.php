<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GloveData extends Model
{
    /** @use HasFactory<\Database\Factories\GloveDataFactory> */
    use HasFactory, SoftDeletes;

    const STATUS_CONNECTED = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_IDLE = 3;
    const STATUS_ERROR = 4;

    protected $fillable = [
        'glove_id','status', 'flex_thumb', 'flex_index', 'flex_middle', 'flex_ring',
        'flex_pinky', 'heartbeat', 'temperature', 'resistance', 'error_flag', 'crc_valid'
    ];

    public static $statusLabels = [
        self::STATUS_CONNECTED => 'connected',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_IDLE => 'idle',
        self::STATUS_ERROR => 'sensor_error',
    ];

    public function glove(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GloveDevice::class, 'glove_id');
    }

    public function scopeCorrectData($query)
    {
        return $query->where('status', '!=', self::STATUS_IDLE)
            ->where('status', '!=', self::STATUS_ERROR)
            ->where(function ($q) {
                $q->where('error_flag', 0)
                    ->orWhereNull('error_flag');
            })
            ->where('crc_valid', true);
    }

    public static function getLastCorrectDataByCustomer($customerId)
    {
      
         return  self::correctData()
            ->whereHas('glove', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->latest('created_at')
            ->first();
    }
}
