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
         Schema::create('tbl_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('student_id')->constrained('tbl_students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('tbl_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('tbl_sections')->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status')->default('paid');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('academic_year_id')->constrained('tbl_academic_years')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['status', 'due_date']);
            $table->index(['status', 'issue_date']);
            $table->index('invoice_type');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_invoices');
    }
};
