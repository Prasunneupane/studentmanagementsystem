<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $classSections = [
            1  => [1, 2],
            2  => [1, 2, 3],
            3  => [1, 2, 3, 4],
            4  => [1, 2],
            5  => [1, 2, 3, 4, 5],
            6  => [2, 3],
            7  => [1, 3, 5],
            8  => [1, 2, 3, 4],
            9  => [2, 4],
            10 => [1, 2, 3, 4, 5],
        ];

        foreach ($classSections as $classId => $sections) {
            foreach ($sections as $sectionId) {
                DB::table('tbl_class_section')->insert([
                    'class_id'   => $classId,
                    'section_id' => $sectionId,
                    'is_active'  => 1,
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ]);
            }
        }
    }
}
