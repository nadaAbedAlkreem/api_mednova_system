<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'gateway',
        'bank_name',
        'account_holder_name',
        'account_number',
        'iban',
        'swift_code',
        'bank_country',
        'status',
        'is_default',
        'verified_at',
        'meta',
    ];

    protected $casts = [
        'is_default'  => 'boolean',
        'verified_at' => 'datetime',
        'meta'        => 'array',
        'account_number'      => 'encrypted',
        'iban'                => 'encrypted',
        'swift_code'          => 'encrypted',
        'account_holder_name' => 'encrypted',
    ];

    /* ================= Relations ================= */

    public function owner()
    {
        return $this->morphTo();
    }

    public function gatewayPayments()
    {
        return $this->hasMany(GatewayPayment::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /* ================= Helpers ================= */

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
