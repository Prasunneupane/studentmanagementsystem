<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

    // Check if FK exists
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'students'
                AND COLUMN_NAME = 'class_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            // Drop only if exists
            if (!empty($fkExists)) {
                $table->dropForeign('students_class_id_foreign');
            }

            // Add new FK
            $table->foreign('class_id')
                ->references('id')
                ->on('tbl_classes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
    //     Schema::table('students', function (Blueprint $table) {
    //     $table->dropForeign(['class_id']);
    // });
    }
};
