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
        Schema::dropIfExists('tbl_terms');

        Schema::create('tbl_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->string('name', 50);
            $table->integer('term_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('tbl_academic_years')
                ->onDelete('cascade');

            $table->index('academic_year_id', 'idx_academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_terms');
    }
};
