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
        Schema::create('glove_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_glove_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedInteger('device_id')->nullable()->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('status', ['connected', 'pairing_in_progress', 'active', 'disconnected', 'error'])->default('disconnected');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glove_devices');
    }
};
