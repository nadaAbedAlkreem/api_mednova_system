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
        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->enum('status',['pending','accepted','active' , 'end','completed','cancelled'])->default('pending')->after('consultant_type');
            $table->boolean('consultant_approved')->default(false); // هل اعتمد المستشار الاستشارة
            $table->boolean('patient_approved')->default(false);    // هل اعتمد المريض الاستشارة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
