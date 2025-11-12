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
        Schema::create('glove_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glove_id')->nullable()->constrained('glove_devices')->cascadeOnDelete();
            $table->foreignId('command_id')->nullable()->constrained('glove_commands')->nullOnDelete();
            $table->smallInteger('error_flag')->unsigned();
            $table->enum('error_type' , ['python_unreachable' , 'command_timeout' , 'invalid_ack' , 'glove_disconnected' , 'crc_error' , 'unknown'])->default('unknown');
            $table->text('error_message')->nullable();
            $table->smallInteger('repeat_count')->default(1);
            $table->timestamp('first_occurrence')->nullable();
            $table->timestamp('last_occurrence')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glove_errors');
    }
};
