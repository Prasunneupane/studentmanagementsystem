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
        Schema::table('tbl_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('fiscal_year_id')->nullable()->after('academic_year_id');
            $table->string('invoice_nepali_date')->after('issue_date');
            $table->foreign('fiscal_year_id')->references('id')->on('tbl_fiscal_year')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
