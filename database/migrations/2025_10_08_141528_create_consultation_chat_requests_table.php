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
        Schema::create('consultation_chat_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('consultant_id')->constrained('customers')->onDelete('cascade');
            $table->enum('consultant_type', ['therapist','rehabilitation_center']);
            $table->enum('status', ['pending','accepted','active' , 'completed','cancelled'])->default('pending');
            $table->integer('patient_message_count')->default(0);
            $table->integer('consultant_message_count')->default(0);
            $table->timestamp('first_patient_message_at')->nullable();
            $table->timestamp('first_consultant_message_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('action_by',['patient','consultable','system'])->nullable();
            $table->text('action_reason')->nullable();
            $table->integer('session_duration_hours')->default(24);
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
        Schema::dropIfExists('consultation_chat_requests');
    }
};
