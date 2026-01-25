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
        Schema::table('tbl_guardians', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_primary_contact');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_guardians', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'created_by', 'updated_by']);
        });
    }
};
