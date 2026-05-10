<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case PENDING_REVIEW      = 'pending_review';
    case PROCESSING          = 'processing';
    case TRANSFERRED         = 'transferred';
    case REJECTED            = 'rejected';
    case CANCELLED_BY_USER   = 'cancelled_by_user';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::PENDING_REVIEW    => $locale === 'en' ? 'Pending Review'       : 'قيد المراجعة',
            self::PROCESSING        => $locale === 'en' ? 'Processing'            : 'قيد المعالجة',
            self::TRANSFERRED       => $locale === 'en' ? 'Transferred'           : 'تم التحويل',
            self::REJECTED          => $locale === 'en' ? 'Rejected'              : 'مرفوض',
            self::CANCELLED_BY_USER => $locale === 'en' ? 'Cancelled by You'      : 'ملغى من قِبَلك',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::TRANSFERRED, self::REJECTED, self::CANCELLED_BY_USER], true);
    }

    public function isCancellable(): bool
    {
        return $this === self::PENDING_REVIEW;
    }
}
