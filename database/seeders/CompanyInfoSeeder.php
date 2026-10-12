<?php

namespace Database\Seeders;

use App\Models\CompanyInfo;
use Illuminate\Database\Seeder;

class CompanyInfoSeeder extends Seeder
{
    /**
     * One initial active row — the same values that used to be hardcoded
     * directly into the print page before the company_info table existed.
     */
    public function run(): void
    {
        CompanyInfo::firstOrCreate(
            ['company_name' => 'Buddha Jyoti English Secondary Boarding School'],
            [
                'company_address' => 'Kathmandu, Bagmati Province, Nepal',
                'company_pan_no' => null,
                'company_phone_no' => '+977-1-4XXXXXX',
                'company_mobile_no' => null,
                'company_email' => 'info@greenwoodschool.edu.np',
                'company_website' => null,
                'logo' => null,
                'is_active' => true,
                'created_by' => 1,
            ]
        );

        $this->command->info('Company info seeded successfully!');
    }
}
