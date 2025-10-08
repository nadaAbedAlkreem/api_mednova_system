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
        Schema::create('therapists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('medical_specialties_id')->constrained('medical_specialties')->onDelete('cascade');
            $table->integer('experience_years')->nullable();
            $table->string('university_name')->nullable();
            $table->text('countries_certified')->nullable();
            $table->year('graduation_year')->nullable();
            $table->string('certificate_file')->nullable();
            $table->string('license_number')->nullable();
            $table->string('license_authority')->nullable();
            $table->string('license_file')->nullable();
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
        Schema::dropIfExists('therapists');
    }
};
