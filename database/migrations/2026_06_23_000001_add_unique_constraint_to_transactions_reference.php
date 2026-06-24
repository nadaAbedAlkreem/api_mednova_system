<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the existing non-unique performance index first; the unique
            // index below serves both uniqueness enforcement and query speed.
            $table->dropIndex('trx_reference_type_idx');

            // One ledger entry per (reference, transaction_type) combination.
            // Prevents duplicate settlement, refund, dispute_freeze, and
            // dispute_release rows for the same consultation at the DB layer,
            // independent of application-level guards.
            $table->unique(
                ['reference_type', 'reference_id', 'transaction_type'],
                'transactions_reference_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_reference_type_unique');

            $table->index(
                ['reference_type', 'reference_id', 'transaction_type'],
                'trx_reference_type_idx'
            );
        });
    }
};
