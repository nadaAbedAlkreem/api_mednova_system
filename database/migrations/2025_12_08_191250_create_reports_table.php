<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('reported_customers_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->enum('category', [
                'consultations',
                'payments',
                'courses',
                'hardware',
                'system',
                'other',

            ]);
            $table->enum('subcategory', [
                // -------- Consultations ----------
                'session_not_done',              // الجلسة لم تتم
                'consultant_absent',             // المستشار لم يحضر
                'patient_absent',                // المريض لم يحضر
                'wrong_medical_info',            // معلومات غير دقيقة
                'misconduct',                    // سوء سلوك
                'technical_issue',               // مشكلة تقنية

                // -------- Payments ----------
                'payment_not_received',
                'wrong_amount_deducted',
                'payment_failed',
                'delayed_transfer',
                'refund_request',

                // -------- Courses ----------
                'course_not_as_described',
                'low_quality_content',
                'missing_updates',
                'access_issue',

                // -------- Hardware ----------
                'device_not_received',
                'device_damaged',
                'missing_items',
                'warranty_issue',
                'replacement_request',

                // -------- System ----------
                'login_issue',
                'bug_issue',
                'suspicious_activity',
                'abuse_report',
                'other'
            ])->nullable();
            $table->string('custom_category')->nullable();
            $table->string('custom_subcategory')->nullable();
            $table->nullableMorphs('related');
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('low');
            $table->enum('status', [
                'new',
                'under_review',
                'awaiting_response',
                'resolved',
                'rejected',
                'closed'
            ])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('customer_id');
            $table->index('reported_customers_id');
            $table->index('category');
            $table->index('subcategory');
            $table->index('related_type');
            $table->index('related_id');
            $table->index('severity');
            $table->index('status');
            $table->index(['category', 'subcategory'], 'reports_category_subcategory_index');
            $table->index(['status', 'severity'], 'reports_status_severity_index');
            $table->index(['related_type', 'related_id'], 'reports_related_index');


        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
