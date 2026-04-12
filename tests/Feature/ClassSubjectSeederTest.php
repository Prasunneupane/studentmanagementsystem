<?php 
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\artisan;

it('runs ClassSubjectTeacher correctly', function () {

    // Count before
    $beforeCount = DB::table('tbl_class_subject')->count();

    // Run seeder
    artisan('db:seed', [
        '--class' => 'ClassSubjectTeacher'
    ])->assertExitCode(0);

    // Count after
    $afterCount = DB::table('tbl_class_subject')->count();

    // ✅ Ensure data inserted
    expect($afterCount)->toBeGreaterThan($beforeCount);

    // ✅ Validate one row structure
    $record = DB::table('tbl_class_subject')->first();

    expect($record)->not->toBeNull()
        ->and($record->class_id)->not->toBeNull()
        ->and($record->section_id)->not->toBeNull()
        ->and($record->subject_id)->not->toBeNull()
        ->and($record->teacher_id)->not->toBeNull()
        ->and($record->academic_year_id)->not->toBeNull();
});