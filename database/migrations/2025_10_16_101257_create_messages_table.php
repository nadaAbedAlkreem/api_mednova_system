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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('chat_request_id')->constrained('consultation_chat_requests')->onDelete('cascade');
            $table->text('message');
            $table->string('attachment')->nullable(); // مسار الصورة أو الملف
            $table->string('attachment_type')->nullable(); // image/pdf/video..
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
