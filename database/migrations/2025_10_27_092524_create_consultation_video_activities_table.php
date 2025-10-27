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
        Schema::create('consultation_video_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_video_request_id');
            $table->foreignId('invitee_id')->constrained('customers')->onDelete('cascade');
            $table->enum('role', ['patient','consultant']);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('joined_method')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->string('data_center')->nullable();
            $table->enum('status', ['joined','left','no_show'])->default('joined');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('consultation_video_request_id', 'cv_activities_request_id_fk')
                ->references('id')
                ->on('consultation_video_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_video_activities');
    }
};
