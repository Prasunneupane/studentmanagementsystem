<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('tbl_states')->insert([
            ['name' => 'Koshi Pradesh', 'nepali_name' => 'कोशी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Madhesh Pradesh', 'nepali_name' => 'मधेश प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bagmati Pradesh', 'nepali_name' => 'बागमती प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gandaki Pradesh', 'nepali_name' => 'गण्डकी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lumbini Pradesh', 'nepali_name' => 'लुम्बिनी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Karnali Pradesh', 'nepali_name' => 'कर्णाली प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sudurpashchim Pradesh', 'nepali_name' => 'सुदूरपश्चिम प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
