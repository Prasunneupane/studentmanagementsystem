<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [

                // Student permissions
                [
                    'setting_key' => 'Default State',
                    'setting_value' => '2',
                    'description' => 'To Set Default State ',
                    'is_active' => 1,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'setting_key' => 'Default District',
                    'setting_value' => '15',
                    'description' => 'Default District',
                    'is_active' => 1,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'setting_key' => 'Default Municipality',
                    'setting_value' => '12',
                    'description' => 'Default Municipality',
                    'is_active' => 1,
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                
];
        \DB::table('tbl_defaul_setting')->insert($defaultSettings);
    }
}
