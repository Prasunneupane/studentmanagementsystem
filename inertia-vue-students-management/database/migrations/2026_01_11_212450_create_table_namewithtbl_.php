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
         if (Schema::hasTable('municipalities')) {
            Schema::drop('municipalities');
        }

        if (Schema::hasTable('districts')) {
            Schema::drop('districts');
        }

        if (Schema::hasTable('states')) {
            Schema::drop('states');
        }
        Schema::create('tbl_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nepali_name')->nullable(); // Assuming a field for the Nepali name
            $table->boolean('is_active')->default(true);
            $table->timestamps(); 
        });
        
        

        Schema::create('tbl_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps(); 
        });

         Schema::create('tbl_municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('district_id')->nullable(); // Assuming a foreign key to districts
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_municipalities');
        Schema::dropIfExists('tbl_districts');
        Schema::dropIfExists('tbl_states');
    }
};
