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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // polymorphic relation
            $table->morphs('reference');

            // transaction type as enum
            $table->enum('transaction_type', [
                'wallet_top_up',
                'subscription_charge',
                'course_purchase',
                'consultation_hold',
                'consultation_release',
                'refund',
                'dispute_release',
                'withdrawal',
                'adjustment'
            ]);

            // entry type: debit or credit
            $table->enum('entry_type', ['debit', 'credit']);

            // related wallet
            $table->foreignId('wallet_id')
                ->nullable()
                ->constrained('wallets')
                ->nullOnDelete();

            $table->decimal('gross_amount', 15, 3);
            $table->decimal('platform_commission', 15, 3)->default(0);
            $table->decimal('vat_amount', 15, 3)->default(0);
            $table->decimal('net_amount', 15, 3);

            $table->string('currency', 3)->default('OMR'); // enum أو string حسب قرارك الحالي

            $table->enum('status', [
                'pending',
                'processing',
                'succeeded',
                'failed',
                'reversed'
            ])->default('pending');

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
