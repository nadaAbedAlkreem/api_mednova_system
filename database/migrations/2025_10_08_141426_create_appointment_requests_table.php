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
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->morphs('schedulable'); // مركز أو مختص
            $table->timestamp('requested_time')->nullable();
            $table->enum('status', ['pending','approved','rejected','completed'])->default('pending');
            $table->text('description')->nullable();
            $table->timestamp('confirmed_start_time')->nullable();
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
