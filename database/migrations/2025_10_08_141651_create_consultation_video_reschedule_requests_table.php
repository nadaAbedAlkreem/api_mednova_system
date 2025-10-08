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
            $table->foreignId('consultation_video_request_id')->constrained('consultation_video_requests')->onDelete('cascade');
            $table->morphs('requester'); // مريض أو مختص/مركز
            $table->enum('status',['pending','approved','rejected','cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('old_start_time')->nullable();
            $table->timestamp('old_end_time')->nullable();
            $table->timestamp('new_start_time')->nullable();
            $table->timestamp('new_end_time')->nullable();
            $table->morphs('action_by'); // من وافق أو رفض
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
