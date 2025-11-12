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
        Schema::create('glove_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('glove_sessions')->cascadeOnDelete();
            $table->foreignId('glove_id')->constrained('glove_devices')->cascadeOnDelete();
            $table->timestamp('timestamp')->nullable();
            $table->smallInteger('flex_thumb')->nullable();
            $table->smallInteger('flex_index')->nullable();
            $table->smallInteger('flex_middle')->nullable();
            $table->smallInteger('flex_ring')->nullable();
            $table->smallInteger('flex_pinky')->nullable();
            $table->decimal('resistance', 6, 2)->nullable();
            $table->decimal('reaction_time', 8, 2)->nullable();
            $table->boolean('is_error')->default(false);
            $table->tinyInteger('error_flag')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glove_performance_logs');
    }
};
