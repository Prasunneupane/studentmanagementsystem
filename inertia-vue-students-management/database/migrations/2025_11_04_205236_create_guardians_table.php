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
        Schema::create('tbl_guardians', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->string('name', 150);
            $table->string('relation', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable(); // optional if you want updated_at

            // Foreign key constraint
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_guardians');
    }
};
