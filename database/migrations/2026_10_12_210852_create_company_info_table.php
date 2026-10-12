<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_company_info', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_address')->nullable();
            $table->string('company_pan_no')->nullable();
            $table->string('company_phone_no')->nullable();
            $table->string('company_mobile_no')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_website')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_company_info');
    }
};
