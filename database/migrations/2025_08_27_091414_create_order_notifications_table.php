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
        Schema::create('order_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('channel' , ['sms', 'email', 'whatsapp', 'push']);
            $table->string('message');
            $table->enum('send_type' ,['relative' , 'absolute']);
            $table->integer('send_after_minutes')->nullable(); // relative
            $table->dateTime('send_at')->nullable(); // absolute
            $table->enum('trigger_event',['register_created','order_created','order_pending', 'order_accepted', 'order_rejected', 'manual'])->nullable(); // relative
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notifications');
    }
};
