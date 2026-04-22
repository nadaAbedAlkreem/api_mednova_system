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
            $table->decimal('consultation_price', 15, 3)->unsigned()->nullable()->change();
            $table->decimal('gross_amount', 15, 3)
                ->unsigned()
                ->nullable()
                ->after('gateway_commission_amount');

            $table->decimal('platform_commission_rate', 5, 2)
                ->unsigned()
                ->nullable()
                ->after('gross_amount');

            $table->decimal('platform_commission_amount', 15, 3)
                ->unsigned()
                ->nullable()
                ->after('platform_commission_rate');

            $table->decimal('consultant_earning_amount', 15, 3)
                ->unsigned()
                ->nullable()
                ->after('platform_commission_amount');
        });

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->decimal('consultation_price', 15, 3)->unsigned()->nullable()->change();
            $table->decimal('gross_amount', 15, 3)
                ->unsigned()
                ->nullable()
                ->after('gateway_commission_amount');

            $table->decimal('platform_commission_rate', 5, 2)
                ->unsigned()
                ->nullable()
                ->after('gross_amount');

            $table->decimal('platform_commission_amount', 15, 3)
                ->unsigned()
                ->nullable()
                ->after('platform_commission_rate');

            $table->decimal('consultant_earning_amount', 15, 3)
                ->unsigned()
                ->nullable()
                ->after('platform_commission_amount');
        });

        Schema::table('gateway_payments', function (Blueprint $table) {
            $table->decimal('net_received_amount', 15, 3)
                ->nullable()
                ->after('amount');
        });

        // Backfill آمن: انسخي القيمة القديمة من net_amount إلى gross_amount
        DB::table('consultation_chat_requests')
            ->whereNotNull('net_amount')
            ->update([
                'gross_amount' => DB::raw('net_amount')
            ]);

        DB::table('consultation_video_requests')
            ->whereNotNull('net_amount')
            ->update([
                'gross_amount' => DB::raw('net_amount')
            ]);
    }

    public function down(): void
    {
        Schema::table('consultation_chat_requests', function (Blueprint $table) {
            $table->dropColumn([
                'gross_amount',
                'platform_commission_rate',
                'platform_commission_amount',
                'consultant_earning_amount',
            ]);
        });

        Schema::table('consultation_video_requests', function (Blueprint $table) {
            $table->dropColumn([
                'gross_amount',
                'platform_commission_rate',
                'platform_commission_amount',
                'consultant_earning_amount',
            ]);
        });

        Schema::table('gateway_payments', function (Blueprint $table) {
            $table->dropColumn('net_received_amount');
        });
    }
};
