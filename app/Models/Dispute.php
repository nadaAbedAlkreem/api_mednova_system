<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_dispute_type',
        'reference_dispute_id',
        'wallet_id',
        'amount',
        'currency',
        'status',
        'resolution',
        'opened_by_type',
        'opened_by_id',
        'resolved_by_type',
        'resolved_by_id',
        'opened_at',
        'resolved_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Polymorphic reference for the dispute
     */
    public function referenceDispute(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Wallet related to this dispute
     */
    public function wallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
