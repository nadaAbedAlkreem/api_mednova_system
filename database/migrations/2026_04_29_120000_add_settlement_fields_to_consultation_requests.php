<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable()->after('released_at');
            $table->boolean('review_window_reminder_sent')->default(false)->after('review_deadline');
        });

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable()->after('released_at');
            $table->boolean('review_window_reminder_sent')->default(false)->after('review_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->dropColumn(['settled_at', 'review_window_reminder_sent']);
        });

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->dropColumn(['settled_at', 'review_window_reminder_sent']);
        });
    }
};
