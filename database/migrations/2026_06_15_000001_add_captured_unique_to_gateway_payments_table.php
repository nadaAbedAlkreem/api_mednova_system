<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A consultation may have multiple gateway_payment rows (one per attempt:
     * initiated → expired → new initiated, or failed → new initiated).
     * A simple UNIQUE on (reference_type, reference_id) would therefore break
     * payment retries.
     *
     * The real invariant is: at most one CAPTURED payment per consultation.
     * MySQL achieves partial-index semantics via a stored generated column that
     * is populated only when status = 'captured' and NULL otherwise. UNIQUE
     * indexes skip NULL values, so non-captured rows never conflict.
     */
    public function up(): void
    {
        // ── Guard: abort if duplicate captures already exist ─────────────────
        $duplicates = DB::table('gateway_payments')
            ->where('status', 'captured')
            ->select('reference_type', 'reference_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('reference_type', 'reference_id')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            Log::channel('financial')->critical('migration.duplicate_captured_payments_detected', [
                'duplicates' => $duplicates->toArray(),
            ]);
            throw new \RuntimeException(
                'Duplicate captured gateway_payments found. Manual resolution required before applying this migration. ' .
                'See financial log channel for details.'
            );
        }

        Schema::table('gateway_payments', function (Blueprint $table) {
            // Stored generated column: non-NULL only for captured rows.
            // NULL rows do not participate in the unique index.
            $table->string('captured_reference', 500)
                ->storedAs("CASE WHEN status = 'captured' THEN CONCAT(reference_type, '-', CAST(reference_id AS CHAR)) ELSE NULL END")
                ->nullable()
                ->after('reference_id');

            $table->unique('captured_reference', 'gp_captured_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('gateway_payments', function (Blueprint $table) {
            $table->dropUnique('gp_captured_reference_unique');
            $table->dropColumn('captured_reference');
        });
    }
};
