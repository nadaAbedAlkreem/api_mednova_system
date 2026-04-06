<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY transaction_type ENUM(
                'payment_record',
                'consultation_credit',
                'withdrawal'
            )
        ");
    }
};
