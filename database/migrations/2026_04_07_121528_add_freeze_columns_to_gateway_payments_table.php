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
         Schema::table('gateway_payments', function (Blueprint $table) {
                $table->integer('attempts')->unsigned()->default(0)->after('payload');
                $table->integer('max_attempts')->unsigned()->default(3)->after('attempts');
                $table->timestamp('frozen_at')->nullable()->after('attempts');
                $table->timestamp('freeze_until')->nullable()->after('frozen_at');
                $table->string('freeze_reason')->nullable()->after('freeze_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateway_payments', function (Blueprint $table) {
                $table->dropColumn(['attempts' , 'max_attempts' ,  'frozen_at', 'freeze_until', 'freeze_reason']);
        });
    }
};
