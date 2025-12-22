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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('currency', 3)->default('OMR');
            $table->decimal('balance', 15, 3)->default(0);
            $table->decimal('available_balance', 15, 3)->default(0);
            $table->decimal('pending_balance', 15, 3)->default(0);
            $table->decimal('frozen_balance', 15, 3)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
