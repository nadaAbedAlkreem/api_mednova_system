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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            $table->morphs('reference');

            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 3);
            $table->string('currency', 3)->default('OMR');

            $table->enum('status', [
                'opened',
                'under_review',
                'resolved',
                'rejected',
                'cancelled'
            ])->default('opened');

            $table->enum('resolution', [
                'refund',
                'release',
                'split',
                'none'
            ])->default('none');

            $table->string('opened_by_type'); // patient | consultant | admin
            $table->unsignedBigInteger('opened_by_id');

            $table->string('resolved_by_type')->nullable(); // admin | system
            $table->unsignedBigInteger('resolved_by_id')->nullable();

            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

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
        Schema::dropIfExists('disputes');
    }
};
