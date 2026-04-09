<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassSubjectTeacher extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classSectionList = \DB::table('tbl_class_sections')->get();
        $subjects = \DB::table('tbl_subjects')->get();
        $teachers = \DB::table('tbl_teachers')->where('is_active', 1)->get();
        $academicYearId = \DB::table('tbl_academic_years')->where('is_active', 1)->first()?->id;
        $data =[];
        foreach ($classSectionList as $classSection) {
            foreach ($subjects as $subject) {
                 \DB::table('tbl_class_subjects')->insert([
                //    $data[]=[ 
                    'class_id' => $classSection->class_id,
                    'section_id' => $classSection->section_id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teachers->random()->id,
                    'academic_year_id' => $academicYearId,
                    'is_optional' => rand(0, 1),
                    'periods_per_week' => rand(3, 6),
                    'max_marks' => 100,
                    'pass_marks' => 40,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                //    ];
                ]);
            }
        }
    }
}
