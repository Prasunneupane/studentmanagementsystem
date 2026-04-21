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
        Schema::create('tbl_student_marks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('exam_schedule_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('academic_year_id');

            $table->decimal('theory_marks', 7, 2)->nullable();
            $table->decimal('practical_marks', 7, 2)->nullable();
            $table->decimal('total_marks', 7, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->text('remarks')->nullable();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('exam_id')
                  ->references('id')->on('tbl_exams')
                  ->onDelete('cascade');

            $table->foreign('exam_schedule_id')
                  ->references('id')->on('tbl_exam_schedules')
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

            $table->foreign('subject_id')
                  ->references('id')->on('tbl_subjects')
                  ->onDelete('cascade');

            $table->foreign('academic_year_id')
                  ->references('id')->on('tbl_academic_years')
                  ->onDelete('cascade');

            // One mark entry per exam+student+subject
            $table->unique(
                ['exam_id', 'student_id', 'subject_id'],
                'unique_exam_student_subject'
            );

            // Indexes
            $table->index(['exam_id', 'class_id', 'section_id'], 'idx_marks_exam_class_section');
            $table->index(['student_id', 'academic_year_id'], 'idx_marks_student_year');
            $table->index('academic_year_id', 'idx_marks_academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_student_marks');
    }
};
