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
        Schema::table('tbl_teachers', function (Blueprint $table) {
             $table->dropColumn('phoneno');
            $table->string('field1')->nullable()->after('photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_teachers', function (Blueprint $table) {
            $table->dropColumn('field1');
            $table->string('phoneno')->after('photo');
        });
    }
};
