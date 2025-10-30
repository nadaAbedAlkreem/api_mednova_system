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
            $table->unsignedBigInteger('zoom_meeting_id')->nullable()->after('consultant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->dropColumn('zoom_meeting_id');
        });
    }
};
