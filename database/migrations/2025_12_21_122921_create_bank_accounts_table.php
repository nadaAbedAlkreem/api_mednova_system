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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');

            $table->string('gateway')->nullable(); // amwal / local_bank

            // معلومات الحساب
            $table->string('bank_name');
            $table->string('account_holder_name');

            $table->string('iban')->nullable();
            $table->string('account_number')->nullable();

            $table->string('swift_code')->nullable();
            $table->string('bank_country', 2)->default('OM');

            // حالة الحساب
            $table->enum('status', ['pending', 'verified', 'rejected', 'blocked'])
                ->default('pending');

            // هل الحساب افتراضي؟
            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
