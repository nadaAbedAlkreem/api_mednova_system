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
        Schema::table('gateway_payments', function (Blueprint $table) {
            $table->string('initiated_lock')->nullable()->unique()->after('gateway_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
