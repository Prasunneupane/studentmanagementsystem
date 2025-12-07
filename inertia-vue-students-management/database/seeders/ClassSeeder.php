<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = range('1', '10'); // generates ['A','B','C',...,'H']
        DB::table('classes')->truncate(); // Clear existing data
        foreach ($sections as $letter) {
            DB::table('classes')->insert([
                'name' => "Class {$letter}",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
