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
            $table->integer('max_messages')->nullable();
            $table->integer('used_messages')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('action_by',['patient','consultable'])->nullable();
            $table->text('action_reason')->nullable();
            $table->timestamp('response_at')->nullable();
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
