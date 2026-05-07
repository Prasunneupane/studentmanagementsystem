<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Factory as FakerFactory;
use App\Faker\NepaliProvider;

class StudentBulkSeeder extends Seeder
{
    private $batchSize = 500;      // Records per batch
    private $totalStudents = 4000;  // Total students to create
    private $faker;
    

    // All state IDs
    private $stateIds = [1, 2, 3, 4, 5, 6, 7];

    // Configuration
    private $classId = [1,2,3,4,5,6,7,8,9,10];
    private $sectionIds = [1, 2, 3, 4, 5];
    private $userId = 1;
    private $statuses = ['enrolled','transferred','graduated','left','promoted'];
    private $districtsByState;
    private $municipalitiesByDistrict;
   
    
    public function run()
    {
        // Disable foreign key checks to allow bulk inserts without related FK records during testing
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // Initialize Faker with Nepali provider
        $this->faker = FakerFactory::create();
        $this->faker->addProvider(new NepaliProvider($this->faker));
        
        $this->districtsByState = DB::table('tbl_districts')
            ->get()
            ->groupBy('state_id');

        $this->municipalitiesByDistrict = DB::table('tbl_municipalities')
            ->get()
            ->groupBy('district_id');
            
        $academicYear = DB::table('tbl_academic_years')
            ->where('is_active', 1)
            ->first();
            
        if (!$academicYear) {
            $this->error('No active academic year found!');
            return;
        }
        
        $this->info('Starting bulk data generation...');
        $this->info("Total students: {$this->totalStudents}");
        $this->info("Batch size: {$this->batchSize}");
        $this->info("Academic year: {$academicYear->academic_year}");
        
        $batches = ceil($this->totalStudents / $this->batchSize);
        $totalInserted = 0;
        $startTime = microtime(true);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $this->info("Processing batch " . ($batch + 1) . " of {$batches}");
            
            $currentBatchSize = min(
                $this->batchSize, 
                $this->totalStudents - ($batch * $this->batchSize)
            );
            
            try {
                DB::beginTransaction();
                
                // Generate students for this batch
                $studentsData = $this->generateStudentsData($currentBatchSize);
                
                // Batch insert students
                DB::table('students')->insert($studentsData);
                
                // IMPORTANT: MySQL lastInsertId() returns the FIRST auto-increment
                // ID of a bulk insert, NOT the last one.
                $firstId = (int) DB::getPdo()->lastInsertId();
                $lastId = $firstId + count($studentsData) - 1;
                
                // Verify the IDs exist in the database
                $actualStudentIds = DB::table('students')
                    ->whereBetween('id', [$firstId, $lastId])
                    ->orderBy('id')
                    ->pluck('id')
                    ->toArray();
                
                // Fallback: if count doesn't match, fetch most recent N via subquery
                if (count($actualStudentIds) !== count($studentsData)) {
                    $this->warn("ID range mismatch! Expected {$currentBatchSize}, got " . count($actualStudentIds));
                    $actualStudentIds = DB::table('students')
                        ->orderByDesc('id')
                        ->limit($currentBatchSize)
                        ->pluck('id')
                        ->sort()       // re-sort ascending
                        ->values()
                        ->toArray();
                }
                
                if (empty($actualStudentIds)) {
                    throw new \Exception("No student IDs retrieved after insert. FirstId: {$firstId}, LastId: {$lastId}");
                }
                
                $this->info("  Student IDs: " . $actualStudentIds[0] . " to " . end($actualStudentIds));
                
                // Generate and insert guardians
                $guardiansData = $this->generateGuardiansData($actualStudentIds);
                if (!empty($guardiansData)) {
                    DB::table('tbl_guardians')->insert($guardiansData);
                    $this->info("  Inserted " . count($guardiansData) . " guardians");
                }
                
                // Generate and insert enrollments
                $enrollmentsData = $this->generateEnrollmentsData($actualStudentIds, $academicYear);
                if (!empty($enrollmentsData)) {
                    DB::table('tbl_enrollments')->insert($enrollmentsData);
                    $this->info("  Inserted " . count($enrollmentsData) . " enrollments");
                }
                
                DB::commit();
                
                $totalInserted += count($actualStudentIds);
                $this->info("✓ Batch " . ($batch + 1) . " completed. Total so far: {$totalInserted}");
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Batch " . ($batch + 1) . " failed: " . $e->getMessage());
                Log::error("Batch seeder failed", [
                    'batch' => $batch + 1,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Stop on first failure to debug
                throw $e;
            }
        }
        
        // Re-enable foreign key checks after seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->info("===================================");
        $this->info("Generation Complete!");
        $this->info("Total students inserted: {$totalInserted}");
        $this->info("Total time: {$duration} seconds");
        $this->info("Average: " . round($totalInserted / $duration, 2) . " records/sec");
        $this->info("===================================");
    }
    
    /**
     * Generate students data array for batch insert
     */
    private function generateStudentsData(int $count): array
    {
        $students = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Step 1: State
            $stateId = $this->faker->randomElement($this->stateIds);

            // Step 2: District (from state)
            $districtList = $this->districtsByState[$stateId] ?? collect();
            $district = $districtList->isNotEmpty()
                ? $this->faker->randomElement($districtList->toArray())
                : null;

            // Step 3: Municipality (from district)
            $municipality = null;
            if ($district) {
                $municipalityList = $this->municipalitiesByDistrict[$district->id] ?? collect();
                $municipality = $municipalityList->isNotEmpty()
                    ? $this->faker->randomElement($municipalityList->toArray())
                    : null;
            }

            // Step 4: Age & DOB
            $age = $this->faker->numberBetween(5, 18);
            $dob = now()
                ->subYears($age)
                ->subDays(rand(0, 365))
                ->format('Y-m-d');

            // Step 5: Names
            $first = $this->faker->nepaliFirstName();
            $middle = $this->faker->nepaliMiddleName();
            $last = $this->faker->nepaliSurname();

            $fullName = trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);

            $students[] = [
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'email' => $this->faker->unique()->safeEmail(),
                'phone' => $this->generateNepaliPhone(),
                'age' => $age,
                'date_of_birth' => $dob,
                'class_id' => $this->faker->randomElement($this->classId),
                'section_id' => $this->faker->randomElement($this->sectionIds) ?? null,
                'father_name' => $this->faker->nepaliFirstName() . ' ' . $last,
                'mother_name' => $this->faker->nepaliFirstName() . ' ' . $last,
                'guardian_name' => $fullName,
                'contact_number' => $this->generateNepaliPhone(),
                'photo' => null,
                'joined_date' => now(),
                'address' => $this->faker->nepaliAddress(),
                'state_id' => $stateId,
                'district_id' => $district?->id,
                'municipality_id' => $municipality?->id,
                'is_active' => 1,
                'created_at' => now(),
                'created_by' => 1,
            ];
        }
        
        return $students;
    }
    
    /**
     * Generate guardians data for given student IDs
     */
    private function generateGuardiansData(array $studentIds): array
    {
        $guardians = [];
        
        foreach ($studentIds as $studentId) {
            // 🔧 FIX: Ensure student_id is positive
            $studentId = abs((int)$studentId);
            
            $numGuardians = rand(1, 2);
            
            for ($i = 0; $i < $numGuardians; $i++) {
                $guardians[] = [
                    'student_id' => $studentId,
                    'name' => $this->faker->nepaliFullName(),
                    'relation' => $this->faker->guardianRelation(),
                    'phone' => $this->generateNepaliPhone(),
                    'email' => $this->faker->optional(0.7)->email(),
                    'occupation' => $this->faker->randomElement([
                        'Teacher', 'Farmer', 'Business', 'Engineer', 
                        'Doctor', 'Government Service', 'Private Job', 'Housewife'
                    ]),
                    'address' => $this->faker->address(),
                    'is_primary_contact' => ($i === 0) ? 1 : 0,
                    'created_by' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        return $guardians;
    }
    
    /**
     * Generate enrollments data for students
     */
    private function generateEnrollmentsData(array $studentIds, $academicYear): array
    {
        $enrollments = [];
        
        foreach ($studentIds as $studentId) {
            // 🔧 CRITICAL FIX: Ensure student_id is positive
            $positiveStudentId = abs((int)$studentId);
            
            // 🔧 CRITICAL FIX: Ensure roll_no uses positive ID
            $rollNumber = $academicYear->academic_year . '-' . 
                          str_pad($positiveStudentId, 4, '0', STR_PAD_LEFT);
            
            // 🔧 FIX: Check if class_id and section_id are valid
            $classId = $this->faker->randomElement($this->classId);
            $sectionId = $this->faker->randomElement($this->sectionIds);
            
            $enrollments[] = [
                'student_id' => $positiveStudentId,  // 🔧 Use positive ID
                'class_id' => $classId,
                'section_id' => $sectionId,
                'academic_year_id' => $academicYear->id,
                'roll_no' => $rollNumber,  // 🔧 Use corrected roll number
                'admission_date' => $this->faker->date('Y-m-d', '-1 year'),
                'status' => $this->faker->randomElement($this->statuses),
                'remarks' => $this->faker->optional(0.3)->sentence(),
                'is_active' => 1,
                'created_by' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return $enrollments;
    }
    
    /**
     * Generate Nepali phone number format
     */
    private function generateNepaliPhone(): string
    {
        $prefix = rand(0, 1) ? '98' : '97';
        return $prefix . rand(10000000, 99999999);
    }
    
    private function info($message)
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            echo $message . "\n";
        }
    }
    
    private function error($message)
    {
        if ($this->command) {
            $this->command->error($message);
        } else {
            echo "ERROR: " . $message . "\n";
        }
    }
    
    private function warn($message)
    {
        if ($this->command) {
            $this->command->warn($message);
        } else {
            echo "WARNING: " . $message . "\n";
        }
    }
}