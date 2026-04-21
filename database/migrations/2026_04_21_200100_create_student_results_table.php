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
        Schema::create('tbl_student_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');

            $table->decimal('total_marks_obtained', 10, 2)->default(0);
            $table->decimal('total_max_marks', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade', 10)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->integer('rank')->nullable();
            $table->enum('status', ['pass', 'fail', 'withheld'])->default('pass');

            $table->boolean('is_finalized')->default(false);
            $table->integer('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->text('remarks')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('exam_id')
                  ->references('id')->on('tbl_exams')
                  ->onDelete('cascade');

            $table->foreign('student_id')
                  ->references('id')->on('students')
                  ->onDelete('cascade');

            $table->foreign('class_id')
                  ->references('id')->on('tbl_classes')
                  ->onDelete('cascade');

            $table->foreign('section_id')
                  ->references('id')->on('tbl_section')
                  ->onDelete('cascade');

            $table->foreign('academic_year_id')
                  ->references('id')->on('tbl_academic_years')
                  ->onDelete('cascade');

            // One result per exam+student
            $table->unique(
                ['exam_id', 'student_id'],
                'unique_exam_student_result'
            );

            // Indexes
            $table->index(['exam_id', 'class_id', 'section_id'], 'idx_results_exam_class_section');
            $table->index(['student_id', 'academic_year_id'], 'idx_results_student_year');
            $table->index('academic_year_id', 'idx_results_academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_student_results');
    }
};
