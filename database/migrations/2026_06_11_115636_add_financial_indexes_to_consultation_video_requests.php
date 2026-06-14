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
            $table->index(
                ['financial_status', 'deleted_at'],
                'video_financial_deleted_idx'
            );

            $table->index(
                ['patient_id', 'financial_status'],
                'video_patient_financial_idx'
            );

            $table->index(
                ['consultant_id', 'financial_status'],
                'video_consultant_financial_idx'
            );

            $table->index(
                ['review_deadline', 'financial_status'],
                'video_review_financial_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->dropIndex('video_financial_deleted_idx');
            $table->dropIndex('video_patient_financial_idx');
            $table->dropIndex('video_consultant_financial_idx');
            $table->dropIndex('video_review_financial_idx');
        });
    }
};
