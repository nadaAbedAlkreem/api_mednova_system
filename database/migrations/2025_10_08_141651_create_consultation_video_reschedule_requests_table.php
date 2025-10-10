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
        Schema::create('consultation_video_reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_video_request_id');
            $table->foreign('consultation_video_request_id', 'cvrr_cvr_id_fk')
                ->references('id')->on('consultation_video_requests')
                ->onDelete('cascade');
            $table->morphs('requester','requester_idx');
            $table->enum('status',['pending','approved','rejected','cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('old_start_time')->nullable();
            $table->timestamp('old_end_time')->nullable();
            $table->timestamp('new_start_time')->nullable();
            $table->timestamp('new_end_time')->nullable();
            $table->morphs('action_by','action_by_idx');
            $table->text('action_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_video_reschedule_requests');
    }
};
