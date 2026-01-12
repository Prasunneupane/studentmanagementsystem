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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        if(Schema::hasTable('classes')){
            Schema::drop('classes');
        }
        

        if(Schema::hasTable('sections')){
            Schema::drop('sections');
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        

        Schema::create('tbl_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_section', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_class_section', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('class_id')->references('id')->on('tbl_classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('tbl_section')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_classes');
        Schema::dropIfExists('tbl_section');
    }
};
