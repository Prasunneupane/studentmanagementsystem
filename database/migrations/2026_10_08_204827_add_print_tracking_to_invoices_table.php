<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_invoices', function (Blueprint $table) {
            $table->unsignedInteger('print_count')->default(0)->after('is_active');
            $table->boolean('is_printed')->default(false)->after('print_count');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_invoices', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'is_printed']);
        });
    }
};
