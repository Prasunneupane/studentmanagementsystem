<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('tbl_academic_years')->insert([
                'academic_year'=>   '2082-2083',
                'start_date'   =>   '2025-09-01',
                'end_date'     =>   '2026-08-30',
                'is_active'    =>    1,
                'created_at'   =>   now(),
                'updated_at'   =>   now(),
            ]);
    }
}
