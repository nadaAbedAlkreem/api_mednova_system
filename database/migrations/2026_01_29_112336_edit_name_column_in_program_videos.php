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
            $table->renameColumn('is_preview', 'is_program_intro');
            $table->text('what_you_will_learn_ar')->nullable()->after('description_en');
            $table->text('what_you_will_learn_en')->nullable()->after('what_you_will_learn_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_videos', function (Blueprint $table) {
            $table->renameColumn('is_program_intro', 'is_preview');

        });
    }
};
