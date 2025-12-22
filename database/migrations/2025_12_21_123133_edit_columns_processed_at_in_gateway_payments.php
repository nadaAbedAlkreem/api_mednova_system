<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gateway_payments', function (Blueprint $table) {
            if (Schema::hasColumn('gateway_payments', 'card_brand')) {
                $table->dropColumn('card_brand');
            }

            if (Schema::hasColumn('gateway_payments', 'card_last4')) {
                $table->dropColumn('card_last4');
            }

            // ربط الدفع ببطاقة محفوظة
            $table->foreignId('card_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('cards')
                ->nullOnDelete();

            // ربط الدفع بحساب بنكي
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('card_id')
                ->constrained('bank_accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateway_payments', function (Blueprint $table) {
            //
        });
    }
};
