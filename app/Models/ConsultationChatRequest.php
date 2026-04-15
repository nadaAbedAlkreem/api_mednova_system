<?php

namespace App\Models;

use App\Enums\ConsultantType;
use App\Enums\ConsultationStatus;
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

    ];
    public const REFERENCE_TYPE = 'consultation';

    protected $casts = [
        'started_at'                 => 'datetime',
        'ended_at'                   => 'datetime',
        'review_deadline'            => 'datetime',
        'released_at'                => 'datetime',
        'first_patient_message_at'   => 'datetime',
        'first_consultant_message_at'=> 'datetime',
        'last_reminder_sent_at'      => 'datetime',
        'suspended_until'            => 'datetime',
        'consultation_price'         => 'decimal:3',
        'gateway_commission_rate'    => 'decimal:3',
        'gateway_commission_amount'  => 'decimal:3',
        'net_amount'                 => 'decimal:3',
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

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'chat_request_id');
    }

    public function unreadMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'chat_request_id')
            ->where('is_read', false);
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->patient_id === $userId || $this->consultant_id === $userId;
    }


}
