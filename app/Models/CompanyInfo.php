<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    protected $table = 'tbl_company_info';

    protected $fillable = [
        'company_name',
        'company_address',
        'company_pan_no',
        'company_phone_no',
        'company_mobile_no',
        'company_email',
        'company_website',
        'logo',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
