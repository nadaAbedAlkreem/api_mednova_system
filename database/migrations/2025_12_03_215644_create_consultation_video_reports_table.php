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
        Schema::create('consultation_video_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_video_request_id');
            $table->integer('duration')->default(0);
            $table->string('zoom_id')->nullable();
            $table->integer('join_time')->default(0);
            $table->integer('leave_time')->default(0);
            $table->string('name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_video_reports');
    }
};
