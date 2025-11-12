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
        Schema::create('glove_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glove_id')->constrained('glove_devices')->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'completed', 'failed'])->default('in_progress');
            $table->string('exercise_type');
            $table->smallInteger('repetitions_target')->default(0);
            $table->smallInteger('repetitions_done')->default(0);
            $table->decimal('avg_reaction_time', 8, 2)->nullable();
            $table->decimal('avg_resistance', 8, 2)->nullable();
            $table->decimal('interval_between_reps', 6, 2)->default(0);
            $table->tinyInteger('default_speed')->default(100);
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->timestamp('session_start')->nullable();
            $table->timestamp('session_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glove_sessions');
    }
};
