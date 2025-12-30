<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

    // Student permissions
    [
        'name' => 'View Students',
        'slug' => 'view_students',
        'module' => 'Students',
        'description' => 'Allows viewing student records',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Create Students',
        'slug' => 'create_students',
        'module' => 'Students',
        'description' => 'Allows creating new students',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Edit Students',
        'slug' => 'edit_students',
        'module' => 'Students',
        'description' => 'Allows editing student information',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Delete Students',
        'slug' => 'delete_students',
        'module' => 'Students',
        'description' => 'Allows deleting student records',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // Subject permissions
    [
        'name' => 'View Subjects',
        'slug' => 'view_subjects',
        'module' => 'Subjects',
        'description' => 'Allows viewing subjects',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Create Subjects',
        'slug' => 'create_subjects',
        'module' => 'Subjects',
        'description' => 'Allows creating new subjects',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Edit Subjects',
        'slug' => 'edit_subjects',
        'module' => 'Subjects',
        'description' => 'Allows editing subject details',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Delete Subjects',
        'slug' => 'delete_subjects',
        'module' => 'Subjects',
        'description' => 'Allows deleting subjects',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // Teacher permissions
    [
        'name' => 'View Teachers',
        'slug' => 'view_teachers',
        'module' => 'Teachers',
        'description' => 'Allows viewing teachers',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Create Teachers',
        'slug' => 'create_teachers',
        'module' => 'Teachers',
        'description' => 'Allows creating new teachers',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Edit Teachers',
        'slug' => 'edit_teachers',
        'module' => 'Teachers',
        'description' => 'Allows editing teacher information',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Delete Teachers',
        'slug' => 'delete_teachers',
        'module' => 'Teachers',
        'description' => 'Allows deleting teacher records',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // Role permissions
    [
        'name' => 'View Roles',
        'slug' => 'view_roles',
        'module' => 'Roles',
        'description' => 'Allows viewing roles',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Create Roles',
        'slug' => 'create_roles',
        'module' => 'Roles',
        'description' => 'Allows creating new roles',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Edit Roles',
        'slug' => 'edit_roles',
        'module' => 'Roles',
        'description' => 'Allows editing roles',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Delete Roles',
        'slug' => 'delete_roles',
        'module' => 'Roles',
        'description' => 'Allows deleting roles',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Assign Permissions',
        'slug' => 'assign_permissions',
        'module' => 'Roles',
        'description' => 'Allows assigning permissions to roles',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // Permission permissions
    [
        'name' => 'View Permissions',
        'slug' => 'view_permissions',
        'module' => 'Permissions',
        'description' => 'Allows viewing permissions',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Create Permissions',
        'slug' => 'create_permissions',
        'module' => 'Permissions',
        'description' => 'Allows creating permissions',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Edit Permissions',
        'slug' => 'edit_permissions',
        'module' => 'Permissions',
        'description' => 'Allows editing permissions',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Delete Permissions',
        'slug' => 'delete_permissions',
        'module' => 'Permissions',
        'description' => 'Allows deleting permissions',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // User permissions
    [
        'name' => 'View Users',
        'slug' => 'view_users',
        'module' => 'Users',
        'description' => 'Allows viewing users',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Create Users',
        'slug' => 'create_users',
        'module' => 'Users',
        'description' => 'Allows creating users',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Edit Users',
        'slug' => 'edit_users',
        'module' => 'Users',
        'description' => 'Allows editing user information',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Delete Users',
        'slug' => 'delete_users',
        'module' => 'Users',
        'description' => 'Allows deleting users',
        'is_active' => 1,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
