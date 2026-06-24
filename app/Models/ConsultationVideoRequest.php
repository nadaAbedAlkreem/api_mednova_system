<?php

namespace App\Models;

use App\Enums\ConsultantType;
use App\Enums\ConsultationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ConsultationVideoRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationVideoRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable =
        [
            'appointment_request_id',
            'patient_id',
            'consultant_id',
            'consultant_type',
            'zoom_meeting_id',
            'status',
            'financial_status',
            'review_deadline',
            'released_at',
            'duration_minutes',
            'video_room_link',
            'action_by',
            'action_reason',
            'consultant_approved',
            'patient_approved',
            'session_duration_hours',
            'last_reminder_sent_at',
            'last_reminder_level',
            'suspended_until',
            'suspension_count',
            'consultation_price',
            'gateway_commission_rate',
            'gateway_commission_amount',
            'expires_at',
            'gross_amount',
            'platform_commission_rate',
            'platform_commission_amount',
            'consultant_earning_amount',
            'settled_at',
            'review_window_reminder_sent',
        ];

    protected $dates = ['deleted_at'];
    public const REFERENCE_TYPE = 'consultation';

    protected $casts = [
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
        'review_deadline'            => 'datetime',
        'released_at'                => 'datetime',
        'last_reminder_sent_at'      => 'datetime',
        'suspended_until'            => 'datetime',
        'expires_at'                 => 'datetime',
        'consultant_approved'        => 'boolean',
        'patient_approved'           => 'boolean',
        'consultation_price'          => 'decimal:3',
        'gateway_commission_rate'     => 'decimal:3',
        'gateway_commission_amount'   => 'decimal:3',
        'platform_commission_rate'    => 'decimal:3',
        'platform_commission_amount'  => 'decimal:3',
        'consultant_earning_amount'   => 'decimal:3',
        'settled_at'                  => 'datetime',
        'review_window_reminder_sent' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($consultationVideoRequest) {
            $consultationVideoRequest->activities()->each(function ($activities) {
                $activities->delete();
            });

        });
    }

    public function consultant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id');
    }

    public function appointmentRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AppointmentRequest::class, 'appointment_request_id');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationVideoActivity::class, 'consultation_video_request_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationVideoReport::class, 'consultation_video_request_id');
    }
    public function isOwnedBy(int $userId): bool
    {
        return $this->patient_id === $userId || $this->consultant_id === $userId;
    }

}
