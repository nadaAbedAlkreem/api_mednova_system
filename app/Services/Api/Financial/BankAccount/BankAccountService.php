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
use Illuminate\Support\Facades\RateLimiter;

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
        $this->validateUniqueness($data['account_number'], $data['iban'] ?? null, $user->id);

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
        RateLimiter::clear($this->otpResendLimiterKey($user->id));
        $this->dispatchOtp($user);

        return $bankAccount;
    }

    public function update(Customer $user, array $data): BankAccount
    {
        $bankAccount = $this->bankAccounts->findByOwner($user->id, get_class($user));

        if (!$bankAccount) {
            throw new DomainException(__('messages.BANK_ACCOUNT_NOT_FOUND'));
        }

        $accountNumberForCheck = $data['account_number'] ?? $bankAccount->account_number;
        $ibanForCheck = $data['iban'] ?? $bankAccount->iban;
        if (($accountNumberForCheck !== $bankAccount->account_number) || ($ibanForCheck !== $bankAccount->iban)) {
            $this->validateUniqueness($accountNumberForCheck, $ibanForCheck, $user->id);
        }
        $bankAccount = $this->bankAccounts->updateAccount($bankAccount, array_merge(
            array_filter($data, fn ($v) => $v !== null),
            ['status' => 'pending', 'verified_at' => null]
        ));
        RateLimiter::clear($this->otpResendLimiterKey($user->id));
        $this->dispatchOtp($user);

        return $bankAccount;
    }
    public function resendOtp(Customer $user): void
    {
        $limiterKey = $this->otpResendLimiterKey($user->id);
        $maxAttempts = 3;
        $decaySeconds = 300; // 5 دقائق

        // 1. التحقق مما إذا كان المستخدم قد تجاوز الحد المسموح
        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $minutes = ceil($seconds / 60);

            // يمكنك تمرير عدد الدقائق المتبقية لرسالة الخطأ إذا أردت
            throw new DomainException(__('messages.OTP_RESEND_LOCKED', ['minutes' => $minutes]));
        }

        // 2. تسجيل محاولة جديدة (سيتم حظره لمدة 5 دقائق بعد المحاولة الثالثة)
        RateLimiter::hit($limiterKey, $decaySeconds);

        // 3. التحقق من وجود حساب بنكي معلق (اختياري ولكن محبذ)
        $bankAccount = $this->bankAccounts->findByOwner($user->id, get_class($user));
        if (!$bankAccount || $bankAccount->status === 'verified') {
            throw new DomainException(__('messages.BANK_ACCOUNT_ALREADY_VERIFIED_OR_NOT_FOUND'));
        }

        // 4. إرسال الكود
        $this->dispatchOtp($user);
    }
    private function validateUniqueness(string $accountNumber, ?string $iban, int $currentUserId): void
    {
        $duplicateExists = BankAccount::query()
            ->where(function ($q) use ($accountNumber, $iban) {
                // استخدام النطاق السحري لـ CipherSweet للبحث في النصوص المشفرة
                $q->whereBlind('account_number', 'account_number_index', $accountNumber);

                if ($iban) {
                    $q->orWhereBlind('iban', 'iban_index', $iban);
                }
            })
            ->where('owner_id', '!=', $currentUserId) // استثناء المستخدم الحالي
            ->whereNull('deleted_at') // مراعاة الحذف الناعم (Soft Delete)
            ->exists();

        if ($duplicateExists) {
            throw new DomainException(__('messages.BANK_ACCOUNT_ALREADY_REGISTERED'));
        }
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
    private function otpResendLimiterKey(int $userId): string
    {
        return "resend_bank_account_otp_limit:{$userId}";
    }
}
