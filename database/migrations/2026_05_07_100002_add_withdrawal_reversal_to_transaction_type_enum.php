<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY transaction_type ENUM(
                'payment_record',
                'consultation_credit',
                'withdrawal',
                'withdrawal_reversal',
                'adjustment',
                'consultation_hold',
                'consultation_release',
                'dispute_freeze',
                'dispute_release',
                'wallet_top_up',
                'subscription_charge',
                'platform_fee',
                'refund'
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY transaction_type ENUM(
                'payment_record',
                'consultation_credit',
                'withdrawal',
                'adjustment',
                'consultation_hold',
                'consultation_release',
                'dispute_freeze',
                'dispute_release',
                'wallet_top_up',
                'subscription_charge',
                'platform_fee',
                'refund'
            )
        ");
    }
};
