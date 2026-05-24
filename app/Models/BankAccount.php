<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class BankAccount extends Model implements CipherSweetEncrypted
{
    use HasFactory, SoftDeletes , UsesCipherSweet;

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
//        'account_number'      => 'encrypted',
//        'iban'                => 'encrypted',
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

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('account_number')
            ->addBlindIndex('account_number', new BlindIndex('account_number_index'))
            ->addField('iban')
            ->addBlindIndex('iban', new BlindIndex('iban_index'));
    }
}
