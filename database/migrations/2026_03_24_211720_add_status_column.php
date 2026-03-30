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
        Schema::table('tbl_exams', function (Blueprint $table) {
                $table->enum('status', ['ongoing','upcoming','completed'])->default('upcoming')->after('is_published');
                $table->index('status', 'idx_exam_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_exams', function (Blueprint $table) {
                $table->dropIndex('idx_exam_status');
                $table->dropColumn('status');
        });
    }
};
