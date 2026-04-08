<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationChatRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationChatRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'consultant_id',
        'consultant_type',
        'status',
        'financial_status',
        'review_deadline',
        'released_at',
        'patient_message_count',
        'consultant_message_count',
        'first_patient_message_at',
        'first_consultant_message_at',
        'session_duration_hours',
        'started_at',
        'ended_at',
        'action_by',
        'action_reason',
        'last_reminder_sent_at',
        'last_reminder_level',
        'suspended_until',
        'suspension_count',
        // Financial columns
        'consultation_price',
        'gateway_commission_rate',
        'gateway_commission_amount',
        'net_amount',

        'created_at',
        'updated_at',
        'deleted_at',

    ];
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'consultation_price' => 'decimal:2',
        'gateway_commission_rate' => 'decimal:2',
        'gateway_commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];


    // المريض
    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id');
    }

    public function consultant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_request_id');
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'chat_request_id')
            ->where('is_read', false);
    }


}
