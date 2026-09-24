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
        Schema::table('tbl_invoice_payments',function(Blueprint $table){
            $table->decimal('return_amount', 15, 2)->default(0)->after('payment_date');
            $table->string('payment_nepali_date')->after('payment_date');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_invoices', function (Blueprint $table) {
            $table->dropForeign(['fiscal_year_id']);
            $table->dropColumn('fiscal_year_id');
            $table->dropColumn('invoice_nepali_date');
        }); 
        Schema::table('tbl_invoice_payments',function(Blueprint $table){
            $table->dropColumn('return_amount');
            $table->dropColumn('payment_nepali_date');
        });
    }
};
