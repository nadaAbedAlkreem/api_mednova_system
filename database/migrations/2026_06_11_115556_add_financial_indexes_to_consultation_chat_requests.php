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
        Schema::table('consultation_chat_requests', function (Blueprint $table) {

            $table->index(
                ['financial_status', 'deleted_at'],
                'chat_financial_deleted_idx'
            );

            $table->index(
                ['patient_id', 'financial_status'],
                'chat_patient_financial_idx'
            );

            $table->index(
                ['consultant_id', 'financial_status'],
                'chat_consultant_financial_idx'
            );

            $table->index(
                ['review_deadline', 'financial_status'],
                'chat_review_financial_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {

            $table->dropIndex('chat_financial_deleted_idx');

            $table->dropIndex('chat_patient_financial_idx');

            $table->dropIndex('chat_consultant_financial_idx');

            $table->dropIndex('chat_review_financial_idx');
        });
    }
};
