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
        Schema::dropIfExists('tbl_exam_schedules');
        Schema::create('tbl_exam_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id');

            $table->date('exam_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('room_no', 50)->nullable();

            $table->decimal('max_theory_marks', 7, 2)->default(80);
            $table->decimal('max_practical_marks', 7, 2)->default(20);
            $table->decimal('max_total_marks', 7, 2)->default(100);
            $table->decimal('pass_marks', 7, 2)->default(40);

            $table->timestamps();

            $table->foreign('exam_id')
                  ->references('id')->on('tbl_exams')
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

            // One schedule row per exam+class+section+subject
            $table->unique(
                ['exam_id', 'class_id', 'section_id', 'subject_id'],
                'unique_exam_schedule'
            );

            // Query indexes
            $table->index(['exam_id', 'class_id'], 'idx_schedule_exam_class');
            $table->index('exam_date', 'idx_schedule_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_exam_schedules');
    }
};
