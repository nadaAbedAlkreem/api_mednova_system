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
        Schema::table('programs', function (Blueprint $table) {
            DB::table('programs')->where('status', 'published')->update(['status' => 'draft']);

                $table->enum('status', ['draft','pending','approved','rejected','archived'])
                    ->default('draft')
                     ->change();


                $table->dropColumn('is_approved');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
