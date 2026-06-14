<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->decimal('gateway_commission_rate', 8, 4)->unsigned()->nullable()->change();
            $table->decimal('gateway_commission_amount', 15, 3)->unsigned()->nullable()->change();
        });

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->decimal('gateway_commission_rate', 8, 4)->unsigned()->nullable()->change();
            $table->decimal('gateway_commission_amount', 15, 3)->unsigned()->nullable()->change();
        });

        DB::statement("
            UPDATE consultation_chat_requests
            SET gateway_commission_amount =
                ROUND(consultation_price * gateway_commission_rate / 100, 3)
            WHERE consultation_price IS NOT NULL
            AND gateway_commission_rate IS NOT NULL
            AND ABS(gateway_commission_amount -
                ROUND(consultation_price * gateway_commission_rate / 100, 3)) > 0.001
        ");

        DB::statement("
            UPDATE consultation_video_requests
            SET gateway_commission_amount =
                ROUND(consultation_price * gateway_commission_rate / 100, 3)
            WHERE consultation_price IS NOT NULL
            AND gateway_commission_rate IS NOT NULL
            AND ABS(gateway_commission_amount -
                ROUND(consultation_price * gateway_commission_rate / 100, 3)) > 0.001
        ");
    }

    public function down(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->decimal('gateway_commission_rate', 5, 2)->unsigned()->nullable()->change();
            $table->decimal('gateway_commission_amount', 10, 2)->unsigned()->nullable()->change();
        });

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->decimal('gateway_commission_rate', 5, 2)->unsigned()->nullable()->change();
            $table->decimal('gateway_commission_amount', 10, 2)->unsigned()->nullable()->change();
        });
    }
};
