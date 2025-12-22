<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    /** @use HasFactory<\Database\Factories\BankAccountFactory> */
    use  HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'bank_name',
        'account_holder_name',
        'iban',
        'account_last4',
        'currency',
        'gateway',
        'gateway_bank_token',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /* ================= Relations ================= */

    public function owner()
    {
        return $this->morphTo();
    }

    public function gatewayPayments()
    {
        return $this->hasMany(GatewayPayment::class);
    }}
