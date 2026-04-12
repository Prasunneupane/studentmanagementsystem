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

    $table->foreignId('class_id')->constrained('tbl_classes')->cascadeOnDelete();
    $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();

    $table->foreignId('section_id')
        ->nullable()
        ->constrained('tbl_section') // ✅ FIX table name
        ->nullOnDelete();

    $table->foreignId('teacher_id')
        ->nullable()
        ->constrained('tbl_teachers')
        ->nullOnDelete();

    $table->foreignId('academic_year_id')
        ->nullable()
        ->constrained('tbl_academic_years')
        ->nullOnDelete();

    $table->boolean('is_optional')->default(false);
    $table->unsignedBigInteger('periods_per_week')->default(0);
    $table->boolean('is_active')->default(true);
    $table->float('max_marks')->default(100);
    $table->float('pass_marks')->default(40);

    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();

    $table->timestamps();

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
