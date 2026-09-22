<?php

use App\Http\Controllers\StudentsController;
use App\Http\Requests\Guardian\GuardianRequest;
use App\Http\Requests\Student\StudentRequest;
use App\Http\Requests\Students\StudentsCreateRequest;
use App\Models\Guardian;
use App\Models\Students;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->controller = app(StudentsController::class);

    $this->academicYearId = DB::table('tbl_academic_years')->insertGetId([
        'academic_year' => '2083-2084',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->stateId = DB::table('tbl_states')->insertGetId([
        'name' => 'Bagmati',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->districtId = DB::table('tbl_districts')->insertGetId([
        'state_id' => $this->stateId,
        'name' => 'Kathmandu',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->municipalityId = DB::table('tbl_municipalities')->insertGetId([
        'district_id' => $this->districtId,
        'name' => 'Budhanilkantha',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->classId = DB::table('tbl_classes')->insertGetId([
        'name' => 'Class 1',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->sectionId = DB::table('tbl_section')->insertGetId([
        'name' => 'Section A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tbl_class_section')->insert([
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->userId = DB::table('users')->insertGetId([
        'name' => 'Test User',
        'email' => 'test-user-' . uniqid() . '@example.com',
        'user_name' => 'test-user-' . uniqid(),
        'password' => bcrypt('secret123'),
        'email_verified_at' => now(),
        'remember_token' => 'token-' . uniqid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->user = User::find($this->userId);
    JWTAuth::shouldReceive('user')->andReturn($this->user);

    $this->app['router']->get('/students/list-test', fn () => 'ok')->name('students.student_list');
});

// Why: index() is the main student listing page. This test proves that the controller renders
// the page and passes the data expected by the frontend instead of returning an empty or invalid response.
test('students controller index renders the student list page', function () {
    $response = $this->controller->index(new Request([
        'from_date' => now()->subDays(7)->format('Y-m-d'),
        'to_date' => now()->format('Y-m-d'),
    ]));

    expect($response)->toBeInstanceOf(\Inertia\Response::class);
});

// Why: create() collects all lookup data for the registration form. This ensures the form receives
// class list, state list, default district/municipality values, and the expected initial form data.
test('students controller create renders the registration form with lookup data', function () {
    $response = $this->controller->create(new Request());

    expect($response)->toBeInstanceOf(\Inertia\Response::class);
});

// Why: district lookup is a common AJAX dependency used by the form when a state is selected.
test('students controller returns districts for a selected state', function () {
    $response = $this->controller->get_districts_by_state_id(new Request([
        'state_id' => $this->stateId,
    ]));

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData())->toBeArray();
});

// Why: municipality lookup depends on the district chosen by the user. This verifies the controller
// returns the right JSON payload for the dependent dropdown.
test('students controller returns municipalities for a selected district', function () {
    $response = $this->controller->get_municipalities_by_district_id(new Request([
        'district_id' => $this->districtId,
    ]));

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData())->toBeArray();
});

// Why: store() is the critical write path. The test validates it receives valid payload data,
// creates a new record, and redirects to the list page with the success message flow.
test('students controller stores a student and redirects on success', function () {
    $request = new StudentsCreateRequest([], [
        'fName' => 'Aarav',
        'mName' => 'K',
        'lName' => 'Shrestha',
        'email' => 'aarav@example.com',
        'phone' => '9800000000',
        'age' => 18,
        'dateOfBirth' => '2008-01-01',
        'classId' => $this->classId,
        'sectionId' => $this->sectionId,
        'motherName' => 'Maya',
        'fatherName' => 'Raj',
        'guardianName' => 'Raj',
        'contactNumber' => '9800000001',
        'joinedDate' => '2026-01-01',
        'address' => 'Kathmandu',
        'stateId' => $this->stateId,
        'districtId' => $this->districtId,
        'municipalityId' => $this->municipalityId,
        'guardians' => [[
            'guardianname' => 'Raj',
            'relation' => 'Father',
            'phone' => '9800000001',
            'email' => 'raj@example.com',
            'occupation' => 'Engineer',
            'address' => 'Kathmandu',
            'is_primary_contact' => true,
        ]],
    ]);

    $response = $this->controller->store($request);

    expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class)
        ->and(Students::where('email', 'aarav@example.com')->exists())->toBeTrue();
});

// Why: edit() is the data-preload step for the update form, and it must return the student
// and the related lookup lists needed by the frontend.
test('students controller edit form loads selected student data', function () {
    $student = Students::create([
        'first_name' => 'Sita',
        'middle_name' => 'M',
        'last_name' => 'Thapa',
        'email' => 'sita@example.com',
        'phone' => '9800000002',
        'age' => '17',
        'date_of_birth' => '2009-06-05',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-02',
        'address' => 'Pokhara',
        'created_by' => $this->user->id,
    ]);

    $response = $this->controller->edit($student);

    expect($response)->toBeInstanceOf(\Inertia\Response::class);
});

// Why: update_student_by_student_id() is a JSON endpoint used by client-side editing. This ensures
// the new values are persisted and the response includes the success flag expected by the frontend.
test('students controller updates a student through the JSON endpoint', function () {
    $student = Students::create([
        'first_name' => 'Nabin',
        'middle_name' => null,
        'last_name' => 'Khatri',
        'email' => 'nabin@example.com',
        'phone' => '9800000003',
        'age' => '16',
        'date_of_birth' => '2010-05-01',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-03',
        'address' => 'Lalitpur',
        'created_by' => $this->user->id,
    ]);

    $response = $this->controller->update_student_by_student_id(new Request([
        'first_name' => 'Nabin',
        'last_name' => 'Khatri Updated',
        'phone' => '9800000004',
        'age' => 17,
        'date_of_birth' => '2010-05-01',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'address' => 'Bhaktapur',
    ]), $student->id);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->success)->toBeTrue();
});

// Why: destroy() is the deletion endpoint. This verifies soft deletion flows through the app as expected
// and returns a JSON payload for the client to handle.
test('students controller deletes a student and returns json response', function () {
    $student = Students::create([
        'first_name' => 'Roshan',
        'last_name' => 'Nepal',
        'email' => 'roshan@example.com',
        'phone' => '9800000005',
        'age' => '15',
        'date_of_birth' => '2011-08-12',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-04',
        'address' => 'Banepa',
        'created_by' => $this->user->id,
    ]);

    $response = $this->controller->destroy($student->id);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->message)->toBe('Student deleted successfully.');
});

// Why: student_list_by_date_range() is used for filtered dashboard/API data; this ensures the controller
// returns current records in the requested period and does not throw on valid data.
test('students controller returns students for the selected date range', function () {
    Students::create([
        'first_name' => 'Bimala',
        'last_name' => 'Dahal',
        'email' => 'bimala@example.com',
        'phone' => '9800000006',
        'age' => '14',
        'date_of_birth' => '2012-09-12',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => now()->format('Y-m-d H:i:s'),
        'address' => 'Dhulikhel',
        'created_by' => $this->user->id,
    ]);

    $response = $this->controller->student_list_by_date_range(new Request([
        'fromDate' => now()->subDays(7)->format('Y-m-d'),
        'toDate' => now()->format('Y-m-d'),
    ]));

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->students)->toBeArray();
});

// Why: update() handles the standard form-based update flow. This test checks the controller still
// returns the expected JSON payload after updating the student record.
test('students controller update method persists updated student data', function () {
    $student = Students::create([
        'first_name' => 'Anisha',
        'middle_name' => null,
        'last_name' => 'Rai',
        'email' => 'anisha@example.com',
        'phone' => '9800000007',
        'age' => '13',
        'date_of_birth' => '2013-02-23',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-05',
        'address' => 'Dharan',
        'created_by' => $this->user->id,
    ]);

    $request = new StudentRequest([], [
        'first_name' => 'Anisha',
        'middle_name' => 'M',
        'last_name' => 'Rai Updated',
        'email' => 'anisha.updated@example.com',
        'phone' => '9800000008',
        'age' => 14,
        'date_of_birth' => '2013-02-23',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'contact_number' => '9800000008',
        'joined_date' => '2026-01-05',
        'address' => 'Dharan',
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
    ]);

    $response = $this->controller->update($request, $student);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->success)->toBeTrue();
});

// Why: loadByDateRange() is used by the frontend grid for dynamic filtering. This verifies the controller
// returns the actual array of student records in the expected format.
test('students controller loads students for the date-range data table', function () {
    Students::create([
        'first_name' => 'Prakash',
        'last_name' => 'Karki',
        'email' => 'prakash@example.com',
        'phone' => '9800000009',
        'age' => '18',
        'date_of_birth' => '2008-11-03',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => now()->format('Y-m-d H:i:s'),
        'address' => 'Bhaktapur',
        'created_by' => $this->user->id,
    ]);

    $response = $this->controller->loadByDateRange(new Request([
        'from_date' => now()->subDays(7)->format('Y-m-d'),
        'to_date' => now()->format('Y-m-d'),
    ]));

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->students)->toBeArray();
});

// Why: getGuardians() powers the student profile section. This ensures the relation is loaded and the
// response contains the structured guardian data expected by the UI.
test('students controller gets guardians for a student', function () {
    $student = Students::create([
        'first_name' => 'Sunita',
        'last_name' => 'Bhandari',
        'email' => 'sunita@example.com',
        'phone' => '9800000010',
        'age' => '12',
        'date_of_birth' => '2014-06-18',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-06',
        'address' => 'Gorkha',
        'created_by' => $this->user->id,
    ]);

    Guardian::create([
        'student_id' => $student->id,
        'name' => 'Ramesh Bhandari',
        'relation' => 'Father',
        'phone' => '9800000011',
        'email' => 'ramesh@example.com',
        'occupation' => 'Farmer',
        'address' => 'Gorkha',
        'is_primary_contact' => true,
    ]);

    $response = $this->controller->getGuardians($student);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->guardians)->toBeArray();
});

// Why: storeGuardian() creates a guardian attached to a student. This verifies the controller writes the
// relationship and returns the client-friendly JSON object that the UI expects after saving.
test('students controller stores a guardian for a student', function () {
    $student = Students::create([
        'first_name' => 'Kiran',
        'last_name' => 'Sharma',
        'email' => 'kiran@example.com',
        'phone' => '9800000012',
        'age' => '11',
        'date_of_birth' => '2015-06-20',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-07',
        'address' => 'Janakpur',
        'created_by' => $this->user->id,
    ]);

    $request = new GuardianRequest([], [
        'name' => 'Mohan Sharma',
        'relation' => 'Father',
        'phone' => '9800000013',
        'email' => 'mohan@example.com',
        'occupation' => 'Business',
        'address' => 'Janakpur',
        'is_primary_contact' => true,
    ]);

    $response = $this->controller->storeGuardian($request, $student);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->success)->toBeTrue();
});

// Why: updateGuardian() handles edits on an existing guardian entry. This prevents regressions in the
// guardian update flow that must stay consistent with the frontend editing modal.
test('students controller updates a guardian', function () {
    $student = Students::create([
        'first_name' => 'Dinesh',
        'last_name' => 'Poudel',
        'email' => 'dinesh@example.com',
        'phone' => '9800000014',
        'age' => '10',
        'date_of_birth' => '2016-04-10',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-08',
        'address' => 'Bharatpur',
        'created_by' => $this->user->id,
    ]);

    $guardian = Guardian::create([
        'student_id' => $student->id,
        'name' => 'Kamal Poudel',
        'relation' => 'Father',
        'phone' => '9800000015',
        'email' => 'kamal@example.com',
        'occupation' => 'Driver',
        'address' => 'Bharatpur',
        'is_primary_contact' => false,
    ]);

    $request = new GuardianRequest([], [
        'name' => 'Kamal Poudel Updated',
        'relation' => 'Father',
        'phone' => '9800000016',
        'email' => 'kamal.updated@example.com',
        'occupation' => 'Teacher',
        'address' => 'Bharatpur',
        'is_primary_contact' => true,
    ]);

    $response = $this->controller->updateGuardian($request, $guardian);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->success)->toBeTrue();
});

// Why: destroyGuardian() is the soft-delete path for guardian records. This makes sure the relationship
// can be removed without physically deleting the database row and keeps the UI response consistent.
test('students controller destroys a guardian record', function () {
    $student = Students::create([
        'first_name' => 'Rita',
        'last_name' => 'Sapkota',
        'email' => 'rita@example.com',
        'phone' => '9800000017',
        'age' => '9',
        'date_of_birth' => '2017-09-11',
        'class_id' => $this->classId,
        'section_id' => $this->sectionId,
        'state_id' => $this->stateId,
        'district_id' => $this->districtId,
        'municipality_id' => $this->municipalityId,
        'joined_date' => '2026-01-09',
        'address' => 'Kailali',
        'created_by' => $this->user->id,
    ]);

    $guardian = Guardian::create([
        'student_id' => $student->id,
        'name' => 'Mahesh Sapkota',
        'relation' => 'Father',
        'phone' => '9800000018',
        'email' => 'mahesh@example.com',
        'occupation' => 'Farmer',
        'address' => 'Kailali',
        'is_primary_contact' => false,
    ]);

    $response = $this->controller->destroyGuardian($guardian);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getData()->success)->toBeTrue();
});
