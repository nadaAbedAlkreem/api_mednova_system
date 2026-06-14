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
        Schema::table('withdrawal_requests', function (Blueprint $table) {

            $table->index(
                ['status', 'deleted_at'],
                'withdrawal_status_deleted_idx'
            );
            $table->index(
                ['owner_type', 'owner_id', 'status'],
                'withdrawal_owner_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {

            $table->dropIndex('withdrawal_status_deleted_idx');
            $table->dropIndex('withdrawal_owner_status_idx');
        });
    }
};
