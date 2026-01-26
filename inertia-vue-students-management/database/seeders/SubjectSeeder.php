<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            // ================= CORE SUBJECTS =================
            [
                'name' => 'English',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject focusing on English language and literature.',
                'code' => 'ENG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nepali',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject focusing on Nepali language, grammar, and literature.',
                'code' => 'NEP',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mathematics',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject covering arithmetic, algebra, and geometry.',
                'code' => 'MATH',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Science',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject covering physics, chemistry, and biology basics.',
                'code' => 'SCI',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Social Studies',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject covering civics, history, geography, and society.',
                'code' => 'SOC',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Health, Population and Environment (HPE)',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject on health education, population studies, and environmental awareness.',
                'code' => 'HPE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Moral Education',
                'is_active' => 1,
                'type' => 'core',
                'description' => 'Compulsory subject focusing on ethics, values, and character development.',
                'code' => 'MOR',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ================= OPTIONAL SUBJECTS =================
            [
                'name' => 'Computer Science',
                'is_active' => 1,
                'type' => 'optional',
                'description' => 'Optional subject introducing programming, IT fundamentals, and digital literacy.',
                'code' => 'CS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Accountancy',
                'is_active' => 1,
                'type' => 'optional',
                'description' => 'Optional subject focusing on basic accounting principles and financial management.',
                'code' => 'ACC',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Economics',
                'is_active' => 1,
                'type' => 'optional',
                'description' => 'Optional subject covering microeconomics and macroeconomics basics.',
                'code' => 'ECO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business Studies',
                'is_active' => 1,
                'type' => 'optional',
                'description' => 'Optional subject introducing entrepreneurship and business fundamentals.',
                'code' => 'BUS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Optional Mathematics',
                'is_active' => 1,
                'type' => 'optional',
                'description' => 'Advanced mathematics for students opting for science or technical streams.',
                'code' => 'OMATH',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ================= EXTRA CURRICULAR SUBJECTS =================
            [
                'name' => 'Computer Club',
                'is_active' => 1,
                'type' => 'extra_curricular',
                'description' => 'Extra-curricular activity focused on coding, robotics, and IT skills.',
                'code' => 'ECS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sports',
                'is_active' => 1,
                'type' => 'extra_curricular',
                'description' => 'Extra-curricular activity covering football, cricket, athletics, and physical fitness.',
                'code' => 'SPORT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Music',
                'is_active' => 1,
                'type' => 'extra_curricular',
                'description' => 'Extra-curricular activity focused on singing, instruments, and music theory.',
                'code' => 'MUS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dance',
                'is_active' => 1,
                'type' => 'extra_curricular',
                'description' => 'Extra-curricular activity focused on traditional and modern dance.',
                'code' => 'DAN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Art & Craft',
                'is_active' => 1,
                'type' => 'extra_curricular',
                'description' => 'Extra-curricular activity focused on drawing, painting, and creative crafts.',
                'code' => 'ART',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \DB::table('tbl_subjects')->insert($subjects);
    }
}
