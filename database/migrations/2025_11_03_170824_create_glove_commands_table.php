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
        Schema::create('glove_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glove_id')->constrained('glove_devices')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('glove_sessions')->nullOnDelete();
            $table->string('command_code');
            $table->tinyInteger('param1')->nullable();
            $table->tinyInteger('param2')->nullable();
            $table->tinyInteger('speed')->default(100);
            $table->enum('ack_status_send', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('ack_received_send_at')->nullable();
            $table->enum('ack_status_device_response', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('ack_received_device_response_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->smallInteger('rep_index')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glove_commands');
    }
};
