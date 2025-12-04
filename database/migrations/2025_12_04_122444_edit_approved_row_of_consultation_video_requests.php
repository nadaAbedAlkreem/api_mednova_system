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
            $table->boolean('consultant_approved')->default(null)->change();
            $table->boolean('patient_approved')->default(null)->change();

        });
    }

    public function down(): void
    {
        Schema::table('consultation_video_requests', function (Blueprint $table) {
        });
    }
};
