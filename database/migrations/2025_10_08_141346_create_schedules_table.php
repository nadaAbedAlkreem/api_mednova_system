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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('customers')->onDelete('cascade');
            $table->enum('consultant_type', ['therapist','rehabilitation_center']);
            $table->json('day_of_week');
            $table->time('start_time_morning')->nullable();
            $table->time('end_time_morning')->nullable();
            $table->boolean('is_have_evening_time')->nullable();
            $table->time('start_time_evening')->nullable();
            $table->time('end_time_evening')->nullable();
            $table->enum('type', ['online','offline'])->default('offline');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
