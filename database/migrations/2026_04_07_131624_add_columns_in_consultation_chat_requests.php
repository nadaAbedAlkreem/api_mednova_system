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
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->timestamp('suspended_until')
                ->nullable()
                ->after('last_reminder_level');

            $table->unsignedTinyInteger('suspension_count')
                ->default(0)
                ->after('suspended_until');});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            //
        });
    }
};
