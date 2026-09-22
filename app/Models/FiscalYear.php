<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;

class FiscalYear extends Model
{
    protected $table = 'tbl_fiscal_year';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'bill_year_code',
        'is_active',
    ];

    public function getStartDateAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getEndDateAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }   

    public function getBillYearCodeAttribute($value)
    {
        return strtoupper($value);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'fiscal_year_id');
    }
    
}
