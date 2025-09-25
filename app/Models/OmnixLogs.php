<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OmnixLog extends Model
{
    use HasFactory;

    protected $table = 'omnix_logs';


    protected $fillable = [
        'customer_id',
        'event',
        'order_id',
        'status',
        'response',
     ];


    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'webhook_received_at' => 'datetime',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    public function order()
    {
        return $this->belongsTo(Order::class);
    }



    public function markAsSuccess(array $response = [], ?string $omnix_user_id = null)
    {
        $this->update([
            'status' => 'success',
            'response_payload' => $response,
            'omnix_user_id' => $omnix_user_id ?? $this->omnix_user_id,
            'completed_at' => now(),
        ]);
    }


    public function markAsFailed(string $errorMessage)
    {
        $this->increment('attempts');
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
