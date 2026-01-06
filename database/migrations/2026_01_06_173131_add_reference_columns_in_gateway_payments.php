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
            $table->string('reference_type')->after('transaction_id');
            $table->unsignedBigInteger('reference_id')->after('reference_type');
            $table->index(['reference_type', 'reference_id']);
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
