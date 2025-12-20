<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'currency',
        'balance',
        'available_balance',
        'pending_balance',
        'frozen_balance',
    ];

    /**
     * Polymorphic owner (Customer | Admin | Consultant | Platform)
     */
    public function owner(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Transactions related to this wallet
     */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Disputes related to this wallet
     */
    public function disputes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Dispute::class);
    }
}
