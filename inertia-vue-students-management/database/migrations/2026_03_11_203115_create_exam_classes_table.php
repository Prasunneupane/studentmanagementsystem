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
         Schema::dropIfExists('tbl_exam_classes');
         Schema::create('tbl_exam_classes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable(); // null = all sections

            $table->foreign('exam_id')
                  ->references('id')->on('tbl_exams')
                  ->onDelete('cascade');

            $table->foreign('class_id')
                  ->references('id')->on('tbl_classes')
                  ->onDelete('cascade');

            $table->foreign('section_id')
                  ->references('id')->on('tbl_section')
                  ->onDelete('cascade');

            // Prevent duplicate exam+class+section combos
            $table->unique(['exam_id', 'class_id', 'section_id'], 'unique_exam_class_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_exam_classes');
    }
};
