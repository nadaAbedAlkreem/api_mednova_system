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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')
                ->constrained('customers')
                ->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('customers')->onDelete('cascade');;
            $table->enum('reviewee_type',[ 'therapist', 'rehabilitation_center', 'platform']);
            $table->decimal('rating', 2, 1);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['reviewee_id', 'reviewee_type']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
