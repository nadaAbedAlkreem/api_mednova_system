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
        Schema::create('glove_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glove_id')->constrained('glove_devices')->cascadeOnDelete();
            $table->tinyInteger('status');
            $table->smallInteger('flex_thumb')->nullable();
            $table->smallInteger('flex_index')->nullable();
            $table->smallInteger('flex_middle')->nullable();
            $table->smallInteger('flex_ring')->nullable();
            $table->smallInteger('flex_pinky')->nullable();
            $table->smallInteger('heartbeat')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('resistance', 6, 2)->nullable();
            $table->tinyInteger('error_flag')->default(0);
            $table->boolean('crc_valid')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glove_data');
    }
};
