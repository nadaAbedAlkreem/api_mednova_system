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
        Schema::create('gateway_payments', function (Blueprint $table) {
            $table->id();
            // ربط الدفع بالـ transaction في النظام
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->string('gateway');
            // الرقم الخارجي للمعاملة في بوابة الدفع
            $table->string('gateway_transaction_id')->nullable();
            // المرجع الخارجي أو رقم الفاتورة للبوابة
            $table->string('gateway_reference')->nullable();
            // طريقة الدفع (card, apple_pay, bank, etc.)
            $table->enum('payment_method', ['card', 'apple_pay', 'bank']);
            // معلومات البطاقة (اختيارية، آخر 4 أرقام فقط)
            $table->string('card_brand')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->decimal('amount', 15, 3);
            $table->string('currency', 3)->default('OMR');
            // حالة الدفع من البوابة
            $table->enum('status', ['initiated', 'authorized', 'captured', 'failed', 'refunded'])
                ->default('initiated');
            // كود الاستجابة من البوابة
            $table->string('response_code')->nullable();

            // رسالة الاستجابة من البوابة
            $table->string('response_message')->nullable();

            // البيانات الكاملة من البوابة (JSON)
            $table->json('payload')->nullable();

            // وقت معالجة الدفع
            $table->timestamp('processed_at')->nullable();

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
        Schema::dropIfExists('gateway_payments');
    }
};
