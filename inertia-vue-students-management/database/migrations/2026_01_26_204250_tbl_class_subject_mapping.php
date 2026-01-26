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
        Schema::create('tbl_class_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->integer('section_id')->nullable();
            $table->integer('teacher_id')->nullable();
            $table->string('academic_year_id')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->unsignedBigInteger('periods_per_week')->default(0);
            $table->boolean('is_active')->default(true);
            $table->float('max_marks')->default(100);
            $table->float('pass_marks')->default(40);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('tbl_classes')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('tbl_subjects')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('tbl_section')->onDelete('set null');
            $table->foreign('teacher_id')->references('id')->on('tbl_teachers')->onDelete('set null');
            $table->foreign('academic_year_id')->references('id')->on('tbl_academic_years')->onDelete('set null');
            $table->index(['class_id', 'subject_id', 'section_id'], 'class_subject_section_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_class_subject');
    }
};
