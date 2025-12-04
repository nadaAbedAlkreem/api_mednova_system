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
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->index('consultant_id');
            $table->index('patient_id');
            $table->index('status');
            $table->index('requested_time');
            $table->index('confirmed_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_requests', function (Blueprint $table) {
            $table->dropIndex(['consultant_id']);
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['requested_time']);
            $table->dropIndex(['confirmed_end_time']);
        });
    }
};
