<?php
// database/migrations/2025_01_01_000000_create_tbl_nepali_calendar.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_nepali_calendar', function (Blueprint $table) {
            $table->id();

            // Identity of this BS month
            $table->unsignedSmallInteger('bs_year');   // 2000 … 2100
            $table->unsignedTinyInteger('bs_month');   // 1 … 12
            $table->unsignedTinyInteger('total_days'); // 29, 30, 31, 32

            // BS boundaries (stored as strings — see note below)
            $table->char('start_bs_date', 10);  // "2082-01-01"
            $table->char('end_bs_date',   10);  // "2082-01-31"

            // AD boundaries (real DATE — sortable, comparable with NOW())
            $table->date('start_ad_date');      // 2025-04-14
            $table->date('end_ad_date');        // 2025-05-14

            // Display helpers
            $table->string('month_name_np', 20);
            $table->string('month_name_en', 20);

            $table->timestamps();

            $table->unique(['bs_year', 'bs_month']);
            $table->index(['start_ad_date', 'end_ad_date']); // AD → BS lookup
            $table->index('total_days');                      // "months with 32 days"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_nepali_calendar');
    }
};