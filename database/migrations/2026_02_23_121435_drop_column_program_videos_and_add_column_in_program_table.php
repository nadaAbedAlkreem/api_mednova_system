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
        Schema::table('program_videos', function (Blueprint $table) {
            $table->dropColumn('what_you_will_learn_ar');
            $table->dropColumn('what_you_will_learn_en');
        });

        // إضافة عمود جديد في جدول programs
        Schema::table('programs', function (Blueprint $table) {
            $table->string('what_you_will_learn_ar')->nullable()->after('description_en');
            $table->string('what_you_will_learn_en')->nullable()->after('what_you_will_learn_ar');
        });
    }

    /**pp
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_videos', function (Blueprint $table) {
            $table->string('what_you_will_learn_ar');
            $table->string('what_you_will_learn_en');
        });

        // حذف العمود الجديد الذي أضفناه
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('what_you_will_learn_ar');
            $table->dropColumn('what_you_will_learn_en');

        });
    }
};
