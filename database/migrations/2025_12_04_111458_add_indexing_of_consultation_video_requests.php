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
        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->boolean('consultant_approved')->default(false)->after('status')->change();
            $table->boolean('patient_approved')->default(false)->after('status')->change();
            $table->index('appointment_request_id');
            $table->index('consultant_id');
            $table->index('patient_id');
            $table->index('consultant_type');
            $table->index('consultant_approved');
            $table->index('patient_approved');
            $table->index('action_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->dropIndex(['consultant_id']);
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['status']);
            $table->dropIndex('appointment_request_id');
            $table->dropIndex('consultant_type');
            $table->dropIndex('consultant_approved');
            $table->dropIndex('patient_approved');
            $table->dropIndex('action_by');

        });
    }
};
