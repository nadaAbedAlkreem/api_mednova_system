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

            $table->index(
                ['wallet_id', 'transaction_type', 'entry_type'],
                'trx_wallet_type_entry_idx'
            );

            $table->index(
                ['reference_type', 'reference_id', 'transaction_type'],
                'trx_reference_type_idx'
            );

            $table->index(
                ['wallet_id', 'deleted_at'],
                'trx_wallet_deleted_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('trx_wallet_type_entry_idx');
            $table->dropIndex('trx_reference_type_idx');
            $table->dropIndex('trx_wallet_deleted_idx');
        });
    }
};
