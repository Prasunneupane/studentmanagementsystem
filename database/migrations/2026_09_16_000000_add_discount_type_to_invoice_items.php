<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_invoice_items', 'discount_type')) {
            Schema::table('tbl_invoice_items', function (Blueprint $table) {
                $table->string('discount_type')->nullable()->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_invoice_items', 'discount_type')) {
            Schema::table('tbl_invoice_items', function (Blueprint $table) {
                $table->dropColumn('discount_type');
            });
        }
    }
};