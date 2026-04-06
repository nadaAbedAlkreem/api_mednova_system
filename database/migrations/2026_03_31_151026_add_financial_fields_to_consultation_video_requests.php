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
            $table->enum('financial_status', [
                'unpaid', 'held', 'review_window', 'withdrawable', 'withdrawn', 'refunded', 'frozen'
            ])->default('unpaid')->after('status');
            $table->timestamp('review_deadline')->after('status')->nullable();
            $table->timestamp('released_at')->after('status')->nullable();
            $table->index(['financial_status', 'review_deadline'], 'ccr_financial_status_review_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_video_requests', function (Blueprint $table) {
                $table->dropIndex(['financial_status', 'review_deadline']);
                $table->dropColumn([
                    'financial_status',
                    'review_deadline',
                    'released_at'
                ]);
        });
    }
};
