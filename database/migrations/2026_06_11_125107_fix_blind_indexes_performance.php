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
        Schema::table('blind_indexes', function (Blueprint $table) {
            $table->index(
                ['indexable_type', 'name', 'value'],
                'blind_indexes_lookup_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blind_indexes', function (Blueprint $table) {
            $table->dropIndex('blind_indexes_lookup_idx');
        });
    }
};
