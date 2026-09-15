<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_invoices';

    protected $fillable = [
        'invoice_number', 'student_id', 'class_id', 'section_id',
        'issue_date', 'due_date', 'status', 'subtotal',
        'discount_type', 'discount_value', 'discount_amount',
        'tax_percentage', 'tax_amount', 'total_amount',
        'paid_amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function getDueAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }
}