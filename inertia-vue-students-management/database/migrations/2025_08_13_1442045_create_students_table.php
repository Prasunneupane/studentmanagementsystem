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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('age');
            $table->date('date_of_birth');
            $table->foreignId('class_id')
                  ->constrained('classes') // references 'id' on 'classes'
                  ->onDelete('cascade');
            $table->string('section_id')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('photo')->nullable();
            $table->datetime('joined_date');
            $table->string('address')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('municipality_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
