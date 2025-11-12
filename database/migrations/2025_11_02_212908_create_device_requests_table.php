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
        Schema::create('device_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'in_contact', 'approved', 'rejected', 'delivered'])->default('pending');
            $table->dateTime('request_date')->useCurrent();
//            $table->enum('contact_method',['phone','email','whatsapp'])->nullable(); // phone, email, whatsapp
            $table->enum('contact_status', ['pending', 'contacted', 'no_response'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_requests');
    }
};
