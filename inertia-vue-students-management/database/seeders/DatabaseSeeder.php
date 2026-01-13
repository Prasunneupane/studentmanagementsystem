<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // $this->call(SectionSeeder::class);
        // $this->call(ClassSeeder::class);
        // $this->call(RoleSeeder::class);
        // $this->call(SectionSeeder::class);
        // $this->call(PermissionSeeder::class);
        // $this->call(RolePermissionSeeder::class);
        //$this->call(AcademicYearSeeder::class);
        // $this->call(StatesSeeder::class);
        // $this->call(DistrictSeeder::class);
        // $this->call(MunicipalitySeeder::class);
        $this->call(DefaultSettingSeeder::class);
        // $this->call(ClassSectionSeeder::class);
    }
}
