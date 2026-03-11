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
        Schema::dropIfExists('tbl_exams');
        Schema::create('tbl_exams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('exam_type', ['unit_test','final','semester','annual']);
            $table->foreignId('academic_year_id')->constrained('tbl_academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('tbl_terms')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_published')->default(false);
            $table->decimal('weightage', 10, 2)->default(0);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

           $table->index(['academic_year_id', 'exam_type'], 'idx_academic_year_exam_type');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_exams');
    }
};
