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
        Schema::table('transactions', function (Blueprint $table) {

            $table->enum('status', ['pending','available','frozen'])
                ->default('available')
                ->change();

            $table->enum('transaction_type', ['payment_record','consultation_credit','withdrawal','adjustment','consultation_hold','consultation_release','dispute_freeze','dispute_release','wallet_top_up','subscription_charge','platform_fee','refund']) // عدّل القيم حسب مشروعك
            ->nullable(false)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
