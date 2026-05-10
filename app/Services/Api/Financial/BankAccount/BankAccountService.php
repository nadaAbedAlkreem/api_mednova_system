<?php

namespace App\Services\Api\Financial\BankAccount;

use App\Mail\BankAccountOtpMail;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Repositories\IBankAccountRepositories;
use App\Repositories\IWalletRepositories;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class BankAccountService
{
    public function __construct(
        protected IWalletRepositories $wallets,
        protected IBankAccountRepositories $bankAccounts,
    ) {}

    public function store(Customer $user, array $data): BankAccount
    {
        $existing = $this->bankAccounts->findByOwner($user->id, get_class($user));

        if ($existing) {
            throw new DomainException(__('messages.BANK_ACCOUNT_ALREADY_EXISTS'));
        }

        $bankAccount = $this->bankAccounts->createManual([
            'owner_type'          => get_class($user),
            'owner_id'            => $user->id,
            'gateway'             => 'manual',
            'bank_name'           => $data['bank_name'],
            'account_holder_name' => $data['account_holder_name'],
            'account_number'      => $data['account_number'],
            'iban'                => $data['iban'] ?? null,
            'swift_code'          => $data['swift_code'] ?? null,
            'bank_country'        => $data['bank_country'] ?? 'OM',
            'status'              => 'pending',
            'is_default'          => true,
        ]);

        $this->dispatchOtp($user);

        return $bankAccount;
    }

    public function update(Customer $user, array $data): BankAccount
    {
        $bankAccount = $this->bankAccounts->findByOwner($user->id, get_class($user));

        if (!$bankAccount) {
            throw new DomainException(__('messages.BANK_ACCOUNT_NOT_FOUND'));
        }

        $bankAccount = $this->bankAccounts->updateAccount($bankAccount, array_merge(
            array_filter($data, fn ($v) => $v !== null),
            ['status' => 'pending', 'verified_at' => null]
        ));

        $this->dispatchOtp($user);

        return $bankAccount;
    }

    public function verifyOtp(Customer $user, string $otp): BankAccount
    {
        $cacheKey = $this->otpCacheKey($user->id);
        $cached   = Cache::get($cacheKey);

        if (!$cached) {
            throw new DomainException(__('messages.OTP_EXPIRED'));
        }

        if ((int) $cached['attempts'] >= 3) {
            Cache::forget($cacheKey);
            throw new DomainException(__('messages.OTP_MAX_ATTEMPTS'));
        }

        Cache::put($cacheKey, array_merge($cached, ['attempts' => $cached['attempts'] + 1]), 600);

        if (!hash_equals($cached['hash'], hash('sha256', $otp))) {
            throw new DomainException(__('messages.OTP_INVALID'));
        }

        $bankAccount = $this->bankAccounts->findByOwner($user->id, get_class($user));

        if (!$bankAccount) {
            Cache::forget($cacheKey);
            throw new DomainException(__('messages.BANK_ACCOUNT_NOT_FOUND'));
        }

        $bankAccount = $this->bankAccounts->updateAccount($bankAccount, [
            'status'      => 'verified',
            'verified_at' => now(),
        ]);

        Cache::forget($cacheKey);

        return $bankAccount;
    }

    public function getDefault(Customer $user): ?BankAccount
    {
        return $this->bankAccounts->findVerifiedByOwner($user->id, get_class($user));
    }

    public function getUserBankAccount(Customer $user): ?BankAccount
    {
        return $this->bankAccounts->findByOwner($user->id, get_class($user));
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function dispatchOtp(Customer $user): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpCacheKey($user->id), [
            'hash'     => hash('sha256', $otp),
            'attempts' => 0,
        ], 600);

        Mail::to($user->email)->queue(new BankAccountOtpMail($user, $otp));
    }

    private function otpCacheKey(int $userId): string
    {
        return "bank_account_otp:{$userId}";
    }
}
