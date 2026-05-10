<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();

            // Owner (Customer)
            $table->morphs('owner');

            // Financial references
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();

            // Amount
            $table->decimal('amount', 12, 3);
            $table->string('currency', 3)->default('OMR');

            // Status
            $table->enum('status', [
                'pending_review',
                'processing',
                'transferred',
                'rejected',
                'cancelled_by_user',
            ])->default('pending_review');

            // Admin processing
            $table->text('admin_note')->nullable();
            $table->text('transfer_reference')->nullable();   // encrypted in model
            $table->string('transfer_proof_path')->nullable();
            $table->string('transfer_proof_hash', 64)->nullable(); // SHA-256

            // Polymorphic: who processed (Admin)
            $table->nullableMorphs('processed_by');
            $table->timestamp('processed_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['owner_type', 'owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
