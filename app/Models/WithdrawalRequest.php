<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'wallet_id',
        'bank_account_id',
        'amount',
        'currency',
        'status',
        'admin_note',
        'transfer_reference',
        'transfer_proof_path',
        'transfer_proof_hash',
        'processed_by_type',
        'processed_by_id',
        'processed_at',
        'meta',
    ];

    protected $casts = [
        'amount'             => 'decimal:3',
        'status'             => WithdrawalStatus::class,
        'transfer_reference' => 'encrypted',
        'meta'               => 'array',
        'processed_at'       => 'datetime',
    ];

    /* ================= Relations ================= */

    public function owner()
    {
        return $this->morphTo();
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function processedBy()
    {
        return $this->morphTo();
    }
}
