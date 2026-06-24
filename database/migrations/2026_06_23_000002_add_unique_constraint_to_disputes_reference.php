<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            // Drop the non-unique composite index created automatically by
            // morphs('reference') in the original create_disputes_table migration.
            // The unique index below serves both uniqueness enforcement and the
            // same query-lookup purpose, so the original index is redundant.
            $table->dropIndex('disputes_reference_type_reference_id_index');

            // Enforce one dispute row per consultation at the database level.
            //
            // Why (reference_type, reference_id) and not just (reference_id):
            //   The disputes table is polymorphic — reference can point to
            //   ConsultationVideoRequest or ConsultationChatRequest. Two rows
            //   with the same reference_id but different reference_type are
            //   different consultations and must not be confused.
            //
            // Soft-delete interaction:
            //   No delete endpoint is exposed and DisputePolicy::delete()
            //   returns false, so soft-deleted dispute rows are not a current
            //   concern. If a soft-delete + recreate workflow is ever introduced
            //   this constraint must be revisited — a plain unique index will
            //   block recreation for the same consultation even after soft delete.
            $table->unique(
                ['reference_type', 'reference_id'],
                'disputes_reference_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropUnique('disputes_reference_unique');

            // Restore the index morphs() would have created originally.
            $table->index(
                ['reference_type', 'reference_id'],
                'disputes_reference_type_reference_id_index'
            );
        });
    }
};
