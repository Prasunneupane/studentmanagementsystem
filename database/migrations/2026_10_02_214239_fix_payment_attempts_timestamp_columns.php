<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `expires_at` was created as the table's first TIMESTAMP column with no
 * explicit default. Under MySQL/MariaDB's legacy `explicit_defaults_for_timestamp
 * = OFF` mode (the default on this install), the FIRST timestamp column in a
 * table automatically gets `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
 * applied by the server — silently resetting expires_at to "now" on every single
 * UPDATE to the row (e.g. updating checkout_url right after creation), which is
 * why every attempt appeared expired immediately. DATETIME columns have no such
 * implicit behavior, so both timestamp-ish columns are converted to DATETIME.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tbl_payment_attempts MODIFY expires_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE tbl_payment_attempts MODIFY verified_at DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tbl_payment_attempts MODIFY expires_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE tbl_payment_attempts MODIFY verified_at TIMESTAMP NULL');
    }
};
