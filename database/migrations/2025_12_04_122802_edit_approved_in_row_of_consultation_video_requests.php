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
        Schema::table('row_of_consultation_video_requests', function (Blueprint $table) {
            $table->boolean('consultant_approved')->nullable()->change(); // هل اعتمد المستشار الاستشارة
            $table->boolean('patient_approved')->nullable()->change();    // هل اعتمد المريض الاستشارة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('row_of_consultation_video_requests', function (Blueprint $table) {
            //
        });
    }
};
