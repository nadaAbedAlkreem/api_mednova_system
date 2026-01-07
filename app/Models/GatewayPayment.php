<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatewayPayment extends Model
{
    /** @use HasFactory<\Database\Factories\GatewayPaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'gateway_payments';

    protected $fillable = [
        'transaction_id',
        'reference_type',
        'reference_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_reference',
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
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * العلاقة مع جدول Transaction
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
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
}
