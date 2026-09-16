<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $table = 'tbl_invoice_items';

    protected $fillable = [
        'invoice_id',
        'fee_type',
        'description', 
        'quantity', 
        'discount_type',
        'discount_percentage',
        'discount_amount',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}