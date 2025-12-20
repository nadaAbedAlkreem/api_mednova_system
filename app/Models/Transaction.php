<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_type',
        'reference_id',
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
        'meta' => 'array',
    ];

    /**
     * Polymorphic reference (Consultation | Subscription | Course | etc.)
     */
    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Wallet related to this transaction
     */
    public function wallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
