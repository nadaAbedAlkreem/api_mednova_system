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
        Schema::table('customers', function (Blueprint $table) {
             $table->enum('account_status', ['active', 'suspended', 'inactive', 'deleted', 'under_review'])
                ->default('active')
                ->after('approval_status');

            // حذف عمود block إذا كان موجود
            if (Schema::hasColumn('customers', 'is_banned')) {
                $table->dropColumn('is_banned');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // حذف عمود account_status
            $table->dropColumn('account_status');

            // إعادة عمود block إذا أردنا الرجوع
            $table->boolean('is_banned')->default(false)->after('approval_status');
        });
    }
};
