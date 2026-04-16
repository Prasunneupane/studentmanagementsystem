<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSubjectTeacher extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $classSectionList = DB::table('tbl_class_section')->get();
    $subjects = DB::table('tbl_subjects')->get();
    $teachers = DB::table('tbl_teachers')->where('is_active', 1)->get();
    $academicYearId = DB::table('tbl_academic_years')->where('is_active', 1)->first()?->id;
    
    // Add debug to see if data exists
    if ($classSectionList->isEmpty()) {
        $this->command->warn('No class sections found!');
    }
    if ($subjects->isEmpty()) {
        $this->command->warn('No subjects found!');
    }
    if ($teachers->isEmpty()) {
        $this->command->warn('No active teachers found!');
    }
    if (!$academicYearId) {
        $this->command->warn('No active academic year found!');
    }
    
    foreach ($classSectionList as $classSection) {
        foreach ($subjects as $subject) {
            DB::table('tbl_class_subject')->insert([
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
            ]);
        }
    }
}
}
