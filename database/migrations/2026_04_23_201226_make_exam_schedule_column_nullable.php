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
        Schema::table('tbl_exam_schedules', function (Blueprint $table) {
            $table->decimal('max_theory_marks', 7, 2)->nullable()->change();
            $table->decimal('max_practical_marks', 7, 2)->nullable()->change();
            $table->decimal('max_total_marks', 7, 2)->nullable()->change();
            $table->decimal('pass_marks', 7, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_exam_schedules', function (Blueprint $table) {
            $table->integer('max_theory_marks')->change();
            $table->integer('max_practical_marks')->change();
            $table->integer('max_total_marks')->change();
            $table->integer('pass_marks')->change();
        });
    }
};
