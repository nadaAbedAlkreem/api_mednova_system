<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            DB::statement("ALTER TABLE wallets ADD CONSTRAINT check_available_balance CHECK (available_balance >= 0)");
            DB::statement("ALTER TABLE wallets ADD CONSTRAINT check_pending_balance CHECK (pending_balance >= 0)");
            DB::statement("ALTER TABLE wallets ADD CONSTRAINT check_frozen_balance CHECK (frozen_balance >= 0)");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            DB::statement("ALTER TABLE wallets DROP CONSTRAINT check_available_balance");
            DB::statement("ALTER TABLE wallets DROP CONSTRAINT check_pending_balance");
            DB::statement("ALTER TABLE wallets DROP CONSTRAINT check_frozen_balance");
        });
    }
};
