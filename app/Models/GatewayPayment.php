<?php

namespace App\Models;

use App\Enums\GatewayPaymentStatus;
use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatewayPayment extends Model
{
    /** @use HasFactory<\Database\Factories\GatewayPaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'gateway_payments';

    protected $fillable = [
//        'transaction_id',
        'reference_type',
        'reference_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_reference',
        'initiated_lock',
        'payment_method',
        'card_id',
        'bank_account_id',
        'amount',
        'currency',
        'country',
        'status',
        'response_code',
        'response_message',
        'payload',
        'attempts',
        'max_attempts',
        'frozen_at',
        'freeze_until',
        'freeze_reason',

    ];

    protected $casts = [
        'amount'      => 'decimal:3',
        'payload'     => 'array',
        'frozen_at'   => 'datetime',
        'freeze_until'=> 'datetime',
    ];


//    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasOne
//    {
//        return $this->hasOne(Transaction::class, 'gateway_payment_id');
//    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function card(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function bankAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(bankAccount::class);
    }

    /* ================= Helpers ================= */

    public function isCardPayment(): bool
    {
        return $this->payment_method === 'card' && !is_null($this->card_id);
    }


    public function isBankPayment(): bool
    {
        return $this->payment_method === 'bank' && !is_null($this->bank_account_id);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', GatewayPaymentStatus::CAPTURED->value);
    }
    public function scopeForUser($query, int $userId, string $morphType = 'App\\Models\\Customer')
    {
        // Gateway payments are linked to a reference (e.g. Consultation).
        // This scope is intentionally left as a hook for the service layer,
        // which knows how to join through Consultation → patient_id.
        return $query;
    }
    public function isFrozen(): bool
    {
        return $this->frozen_at !== null
            && ($this->freeze_until === null || $this->freeze_until->isFuture());
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            PaymentMethodType::METHOD_CARD->value => 'Credit / Debit Card',
            PaymentMethodType::METHOD_APPLE_PAY->value => 'Apple Pay',
            PaymentMethodType::METHOD_BANK->value       => 'Bank Transfer',
            default                => ucfirst($this->payment_method),
        };
    }

}
