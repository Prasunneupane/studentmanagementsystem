<?php

namespace Database\Seeders;

use App\Models\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $roles = [
            [
                'name' => 'Super Admin',
                'description' => 'System owner with full permissions',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Principal',
                'description' => 'Head of the school',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vice Principal',
                'description' => 'Assists the principal',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Teacher',
                'description' => 'Teaches subjects to students',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Class Teacher',
                'description' => 'Responsible for a specific class',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Accountant',
                'description' => 'Manages fees and financial records',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Librarian',
                'description' => 'Manages library books and records',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Receptionist',
                'description' => 'Handles front desk and inquiries',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Student',
                'description' => 'School student',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Parent',
                'description' => 'Guardian of student',
                'created_by' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                
            ],
        ];
         
         foreach ($roles as $role) {
             Roles::create($role);
         }
    }
}
