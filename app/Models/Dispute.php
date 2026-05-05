<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_type',
        'reference_id',
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
        'amount' => 'decimal:3',
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function openedBy()
    {
        return $this->morphTo();
    }

    public function resolvedBy()
    {
        return $this->morphTo();
    }
}
