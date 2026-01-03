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
            $table->enum('status',['pending','accepted','active','completed','cancelled' , 'pending_review'])->default('pending')->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_video_requests', function (Blueprint $table) {
            //
        });
    }
};
