<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderNotification extends Model
{
    /** @use HasFactory<\Database\Factories\OrderNotificationFactory> */
    use HasFactory , SoftDeletes;
    protected $fillable = [
        'channel' ,
        'message' ,
        'send_type' ,
        'send_after_minutes' ,
        'send_at' ,
        'trigger_event' ,
        'status' ,
        'sent_at' ,
    ];


}
