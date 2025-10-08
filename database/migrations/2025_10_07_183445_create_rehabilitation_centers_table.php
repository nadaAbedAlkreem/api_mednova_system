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
        Schema::create('rehabilitation_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->integer('year_establishment')->nullable();

            $table->string('license_number')->nullable();
            $table->string('license_authority')->nullable();
            $table->string('license_file')->nullable();

            $table->boolean('has_commercial_registration')->default(false);
            $table->string('commercial_registration_number')->nullable();
            $table->string('commercial_registration_authority')->nullable();
            $table->string('commercial_registration_file')->nullable();

            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rehabilitation_centers');
    }
};
