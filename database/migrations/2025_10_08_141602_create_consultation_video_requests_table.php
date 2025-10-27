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
        Schema::create('consultation_video_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_request_id')->nullable()->constrained('appointment_requests')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('consultant_id')->constrained('customers')->onDelete('cascade');
            $table->enum('consultant_type', ['therapist','rehabilitation_center']);
            $table->enum('status',['pending','accepted','active','completed','cancelled'])->default('pending');
            $table->integer('duration_minutes')->default(10);// توقيت الفراغ بين كل جلسة
            $table->string('video_room_link')->nullable();
            $table->enum('action_by',['patient','consultable','system'])->nullable();
            $table->text('action_reason')->nullable();
            $table->integer('session_duration_hours')->default(1); // وقت الافتراضي لكل جلسة
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->integer('last_reminder_level')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_video_requests');
    }
};
