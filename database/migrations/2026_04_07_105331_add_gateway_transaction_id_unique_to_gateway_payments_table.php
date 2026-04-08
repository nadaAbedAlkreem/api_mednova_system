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
                $table->unique('gateway_transaction_id', 'gp_gateway_transaction_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateway_payments', function (Blueprint $table) {
                $table->dropUnique('gp_gateway_transaction_id_unique');

        });
    }
};
