<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE consultation_chat_requests
            MODIFY COLUMN financial_status ENUM(
                'unpaid',
                'held',
                'review_window',
                'withdrawable',
                'withdrawn',
                'refunded',
                'refunded_internal',
                'frozen',
                'payment_suspended'
            ) NOT NULL DEFAULT 'unpaid'
        ");

        DB::statement("
            ALTER TABLE consultation_video_requests
            MODIFY COLUMN financial_status ENUM(
                'unpaid',
                'held',
                'review_window',
                'withdrawable',
                'withdrawn',
                'refunded',
                'refunded_internal',
                'frozen',
                'payment_suspended'
            ) NOT NULL DEFAULT 'unpaid'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE consultation_chat_requests
            MODIFY COLUMN financial_status ENUM(
                'unpaid',
                'held',
                'review_window',
                'withdrawable',
                'withdrawn',
                'refunded',
                'frozen',
                'payment_suspended'
            ) NOT NULL DEFAULT 'unpaid'
        ");
        DB::statement("
            ALTER TABLE consultation_video_requests
            MODIFY COLUMN financial_status ENUM(
                'unpaid',
                'held',
                'review_window',
                'withdrawable',
                'withdrawn',
                'refunded',
                'frozen',
                'payment_suspended'
            ) NOT NULL DEFAULT 'unpaid'
        ");
    }
};
