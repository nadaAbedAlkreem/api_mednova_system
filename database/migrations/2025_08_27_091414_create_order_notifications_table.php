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
            $table->foreignId('order_id')
            ->constrained('orders')
            ->onDelete('cascade');
            $table->enum('channel' , ['sms', 'email', 'whatsapp', 'push']);
            $table->string('message');
            $table->enum('send_type' ,['relative' , 'absolute']);
            $table->integer('send_after_minutes')->nullable();
            $table->date('send_at')->nullable();
            $table->enum('trigger_event',['order_created','order_pending', 'order_accepted', 'order_rejected', 'manual']);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->date('sent_at')->nullable();
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
