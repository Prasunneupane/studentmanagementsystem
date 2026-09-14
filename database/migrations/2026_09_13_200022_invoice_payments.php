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
         
         Schema::create('tbl_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('tbl_invoices')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('paid_on');
            $table->string('payment_method')->default('cash');
            $table->string('payment_gateway')->nullable();
            $table->string('payment_code')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('payment_status')->default('success');
            // Cheque-specific
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('payment_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // indexes for performance optimization
            $table->index(['invoice_id']);
            // $table->index(['student_id', 'payment_date']);
            $table->index(['payment_method', 'payment_date']);
            $table->index('reference_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_invoice_payments');
    }
};
