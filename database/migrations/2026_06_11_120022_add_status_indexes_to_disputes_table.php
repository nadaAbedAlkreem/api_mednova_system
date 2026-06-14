<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {

            $table->index(
                ['status', 'deleted_at'],
                'disputes_status_deleted_idx'
            );

            // مهم للـ admin dashboard counts
            $table->index(
                ['reference_type', 'status'],
                'disputes_reference_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {

            $table->dropIndex('disputes_status_deleted_idx');
            $table->dropIndex('disputes_reference_status_idx');
        });
    }
};
