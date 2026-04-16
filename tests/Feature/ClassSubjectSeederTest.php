<?php

use Illuminate\Support\Facades\DB;
use function Pest\Laravel\artisan;

it('runs ClassSubjectTeacher correctly', function () {
    // First seed the dependencies
    artisan('db:seed', ['--class' => 'SectionSeeder']);
    artisan('db:seed', ['--class' => 'ClassSeeder']);
    artisan('db:seed', ['--class' => 'AcademicYearSeeder']);
    artisan('db:seed', ['--class' => 'SubjectSeeder']);
    artisan('db:seed', ['--class' => 'TeacherSeeder']);
    artisan('db:seed', ['--class' => 'ClassSectionSeeder']);
    
    // Run the seeder being tested
    artisan('db:seed', ['--class' => 'ClassSubjectTeacher'])->assertExitCode(0);
    
    // Debug: See what was inserted
    $allRecords = DB::table('tbl_class_subject')->get();
    dump($allRecords->toArray()); // This will show data during test
    
    // Or count and dump
    $count = DB::table('tbl_class_subject')->count();
    dump("Total records inserted: " . $count);
    
    // Your assertions
    $afterCount = DB::table('tbl_class_subject')->count();
    expect($afterCount)->toBeGreaterThan(0);
});