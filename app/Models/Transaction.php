<?php

namespace App\Models;

use App\Enums\EntryType;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'gateway_payment_id',
        'transaction_type',
        'entry_type',
        'wallet_id',
        'gross_amount',
        'platform_commission',
        'vat_amount',
        'net_amount',
        'currency',
        'status',
        'meta',
    ];

    protected $casts = [
        'gross_amount'        => 'decimal:3',
        'platform_commission' => 'decimal:3',
        'vat_amount'          => 'decimal:3',
        'net_amount'          => 'decimal:3',
        'meta'                => 'array',
    ];

    /**
     * Polymorphic reference (Consultation | ProgramEnrollment | PackageFeature | etc.)
     */
    /* ================= Relations ================= */

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function gatewayPayment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GatewayPayment::class, 'gateway_payment_id');
    }
    /**
     * Wallet related to this transaction
     */
    public function wallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function scopeCredits($query)
    {
        return $query->where('entry_type', EntryType::ENTRY_CREDIT->value );
    }

    public function scopeDebits($query)
    {
        return $query->where('entry_type',  EntryType::ENTRY_DEBIT->value);
    }

    public function scopeOfType($query, string|array $type)
    {
        return $query->whereIn('transaction_type', (array) $type);
    }


    public function getConsultationIdAttribute(): ?int
    {
        $meta = $this->meta ?? [];

        return $meta['consultation_id'] ?? null;
    }

    public function getSignedAmountAttribute(): string
    {
        if ($this->entry_type === EntryType::ENTRY_DEBIT->value) {
            return bcsub('0', (string) $this->net_amount, 3);
        }

        return (string) $this->net_amount;
    }

    public function scopeVisibleToUser($query)
    {
        return $query->whereIn('transaction_type', TransactionType::visibleForConsultant());
    }


}
