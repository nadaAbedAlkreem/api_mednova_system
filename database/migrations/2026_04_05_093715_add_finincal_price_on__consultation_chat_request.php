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
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->decimal('consultation_price', 10, 2)
                ->unsigned()
                ->nullable()
                ->after('action_reason');

            $table->decimal('gateway_commission_rate', 5, 2)
                ->unsigned()
                ->nullable()
                ->after('consultation_price');

            $table->decimal('gateway_commission_amount', 10, 2)
                ->unsigned()
                ->nullable()
                ->after('gateway_commission_rate');

            $table->decimal('net_amount', 10, 2)
                ->unsigned()
                ->nullable()
                ->after('gateway_commission_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_price',
                'gateway_commission_rate',
                'gateway_commission_amount',
                'net_amount',
            ]);
        });
    }
};
