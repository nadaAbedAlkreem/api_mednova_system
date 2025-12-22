<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    /** @use HasFactory<\Database\Factories\CardFactory> */
    use  HasFactory , SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'gateway',
        'card_brand',
        'card_last4',
        'expiry_month',
        'expiry_year',
        'gateway_card_token',
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
