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
        Schema::create('appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('customers')->onDelete('cascade');
            $table->enum('consultant_type', ['therapist','rehabilitation_center']);
            $table->foreignId('patient_id')->constrained('customers')->onDelete('cascade');
            $table->string('requested_day')->nullable();
            $table->timestamp('requested_time')->nullable();
            $table->enum('status', ['pending','approved','rejected','completed'])->default('pending');
            $table->enum('type_appointment', ['online' , 'offline'])->default('online');
            $table->timestamp('confirmed_end_time')->nullable();
            $table->integer('session_duration')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_requests');
    }
};
