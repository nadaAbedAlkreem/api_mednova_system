<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These columns were added as nullable when the financial system was
     * introduced. They are always populated by the application before any
     * financial operation can run (settlement, refund, audit).
     *
     * Keeping them nullable means a PHP bug or manual DB edit could silently
     * store NULL, which casts to 0.0 at runtime and corrupts settlement math
     * without throwing an error.
     *
     * Strategy:
     *   1. Backfill any existing NULLs with 0.000 (safe: financial guards
     *      already reject consultation_price <= 0 before settling or refunding).
     *   2. Alter the columns to NOT NULL DEFAULT 0.000.
     */
    private array $tables = [
        'consultation_chat_requests',
        'consultation_video_requests',
    ];

    private array $columns = [
        'consultation_price',
        'gross_amount',
        'platform_commission_amount',
        'consultant_earning_amount',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            // Step 1: replace NULLs with 0.000 so the NOT NULL alter succeeds
            foreach ($this->columns as $column) {
                DB::table($table)
                    ->whereNull($column)
                    ->update([$column => '0.000']);
            }

            // Step 2: alter each column to NOT NULL with default 0.000
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('consultation_price', 15, 3)
                    ->unsigned()->default('0.000')->nullable(false)->change();
                $blueprint->decimal('gross_amount', 15, 3)
                    ->unsigned()->default('0.000')->nullable(false)->change();
                $blueprint->decimal('platform_commission_amount', 15, 3)
                    ->unsigned()->default('0.000')->nullable(false)->change();
                $blueprint->decimal('consultant_earning_amount', 15, 3)
                    ->unsigned()->default('0.000')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('consultation_price', 15, 3)
                    ->unsigned()->nullable()->change();
                $blueprint->decimal('gross_amount', 15, 3)
                    ->unsigned()->nullable()->change();
                $blueprint->decimal('platform_commission_amount', 15, 3)
                    ->unsigned()->nullable()->change();
                $blueprint->decimal('consultant_earning_amount', 15, 3)
                    ->unsigned()->nullable()->change();
            });
        }
        // Note: rows that were NULL before up() cannot be distinguished from
        // rows that were legitimately 0.000. The down() migration restores
        // the nullable column type but cannot restore original NULL values.
    }
};
