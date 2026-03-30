<?php

namespace Database\Seeders;

use DB;
use File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $path = database_path('sqlfile/municipality.sql');

        if (! File::exists($path)) {
            throw new \Exception("SQL file not found at: " . $path);
        }

        DB::unprepared(File::get($path));
    }
}
