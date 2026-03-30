<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = range('A', 'H'); // generates ['A','B','C',...,'H']

        foreach ($sections as $letter) {
            DB::table('tbl_section')->insert([
                'name' => "Section {$letter}",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    
    }
}
