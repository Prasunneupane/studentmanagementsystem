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
        Schema::create('tbl_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained('tbl_invoices')->cascadeOnDelete();
            $table->string('gateway');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NPR');
            $table->string('status')->default('pending');
            $table->string('provider_transaction_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->text('checkout_url')->nullable();
            $table->text('qr_payload')->nullable();
            // dateTime, not timestamp: a TIMESTAMP column with no explicit
            // default gets MySQL's implicit DEFAULT/ON UPDATE CURRENT_TIMESTAMP
            // behavior under explicit_defaults_for_timestamp=OFF (the default on
            // many installs) if it's the first such column in the table — which
            // would silently reset this value to "now" on every UPDATE.
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('status');
            $table->index(['gateway', 'status']);
            $table->unique('provider_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_payment_attempts');
    }
};
