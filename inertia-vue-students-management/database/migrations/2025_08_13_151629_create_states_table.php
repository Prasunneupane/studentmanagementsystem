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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nepali_name')->nullable(); // Assuming a field for the Nepali name
            $table->timestamps();
        });

    DB::table('states')->insert([
        ['name' => 'Koshi Pradesh', 'nepali_name' => 'कोशी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Madhesh Pradesh', 'nepali_name' => 'मधेश प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Bagmati Pradesh', 'nepali_name' => 'बागमती प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Gandaki Pradesh', 'nepali_name' => 'गण्डकी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Lumbini Pradesh', 'nepali_name' => 'लुम्बिनी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Karnali Pradesh', 'nepali_name' => 'कर्णाली प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Sudurpashchim Pradesh', 'nepali_name' => 'सुदूरपश्चिम प्रदेश', 'created_at' => now(), 'updated_at' => now()],
    ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
