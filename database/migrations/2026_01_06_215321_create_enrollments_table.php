<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_enrollments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');

            // Enrollment info
            $table->string('roll_no', 20)->nullable();
            $table->date('admission_date')->nullable();

            $table->enum('status', [
                'enrolled',
                'transferred',
                'graduated',
                'left',
                'promoted'
            ])->default('enrolled');

            $table->text('remarks')->nullable();

            // Soft delete + active flag
            $table->boolean('is_active')->default(true);
            // $table->softDeletes();

            // Audit fields
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Constraints & Indexes
            |--------------------------------------------------------------------------
            */

            // Prevent duplicate enrollment per academic year
            $table->unique(
                ['student_id', 'academic_year_id'],
                'unique_student_academic_year'
            );

            // Indexes
            $table->index(['student_id', 'academic_year_id'], 'idx_student_year');
            $table->index(['class_id', 'section_id'], 'idx_class_section');

            // Foreign key constraints
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->foreign('academic_year_id')->references('id')->on('tbl_academic_years');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_enrollments');
    }
};
