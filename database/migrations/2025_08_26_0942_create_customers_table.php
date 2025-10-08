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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->enum('gender' , ['Male' , 'Female'])->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->date('birth_date')->nullable();
            $table->string('image')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('fcm_token')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->enum('type_account' , ['therapist' ,'rehabilitation_center' , 'patient']);
            $table->enum('status' , ['active' , 'not_active'])->default('not_active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
