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
        Schema::table('consultation_video_activities', function (Blueprint $table) {
            $table->dropForeign(['consultation_video_activities_invitee_id_foreign']);
            $table->dropColumn('role');
        });
    }

    /** * Reverse the migrations. */
    public function down(): void
    {
        Schema::table('consultation_video_activities', function (Blueprint $table) {
            $table->foreign('consultation_video_activities_invitee_id_foreign')->references('id')->on('customers')->onDelete('cascade');
            $table->enum('role', ['patient', 'consultant']);
        });
    }
};
