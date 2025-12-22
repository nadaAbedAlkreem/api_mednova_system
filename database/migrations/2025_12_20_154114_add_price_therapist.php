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
        Schema::table('therapists', function (Blueprint $table) {
            $table->decimal('video_consultation_price', 15, 3)->nullable()->check('video_consultation_price >= 0')->after('license_file');
            $table->decimal('chat_consultation_price', 15, 3)->nullable()->check('chat_consultation_price >= 0')->after('license_file');
            $table->string('currency', 3)->default('OMR');

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
