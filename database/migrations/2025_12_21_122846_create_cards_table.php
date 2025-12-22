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
        Schema::create('cards', function (Blueprint $table) {
                $table->id();
                $table->morphs('owner_card');
                $table->string('gateway'); // amwal, stripe, tap ...

                // token من بوابة الدفع (الأهم)
                $table->string('card_token')->nullable();

                // معلومات غير حساسة
                $table->string('card_brand');          // visa, mastercard
                $table->string('card_last4', 4);
                $table->string('card_country', 2)->nullable();
                $table->string('expiry_month', 2);
                $table->string('expiry_year', 4);

                // هل هي البطاقة الافتراضية؟
                $table->boolean('is_default')->default(false);
                $table->enum('status', ['active', 'expired', 'blocked'])
                    ->default('active');

                $table->timestamps();
                $table->softDeletes();

                $table->index(['gateway', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
