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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'active',
                'not_active',
                'pending',
                'approved',
                'rejected'
            ])->default('pending')->change();
        });

        // 2️⃣ تحويل البيانات القديمة
        DB::table('customers')->where('status', 'active')
            ->update(['status' => 'approved']);

        DB::table('customers')->where('status', 'not_active')
            ->update(['status' => 'pending']);

        // 3️⃣ حذف القيم القديمة من enum
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->change();
        });

        // 4️⃣ إعادة تسمية العمود
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('status', 'approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('approval_status', 'status');
        });

        // 2️⃣ السماح بكل القيم مؤقتًا
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'active',
                'not_active',
                'pending',
                'approved',
                'rejected'
            ])->default('not_active')->change();
        });

        // 3️⃣ إعادة القيم القديمة
        DB::table('customers')
            ->where('status', 'approved')
            ->update(['status' => 'active']);

        DB::table('customers')
            ->where('status', 'pending')
            ->update(['status' => 'not_active']);

        // 4️⃣ إعادة enum للحالة الأصلية فقط
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', ['active', 'not_active'])
                ->default('not_active')
                ->change();
        });
    }
};
