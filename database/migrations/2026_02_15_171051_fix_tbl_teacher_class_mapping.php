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
        Schema::dropIfExists('tbl_class_teachers');
       Schema::create('tbl_class_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('tbl_classes');
            $table->foreignId('section_id')->nullable()->constrained('tbl_section');
            $table->foreignId('teacher_id')->constrained('tbl_teachers');
            $table->foreignId('academic_year_id')->constrained('tbl_academic_years');
            $table->boolean('is_class_teacher')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'section_id', 'academic_year_id'], 'unique_class_section_year');
            $table->index(['teacher_id', 'academic_year_id'], 'idx_teacher_academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_class_teachers');
    }
};
