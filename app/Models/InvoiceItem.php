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
        'rate',
        'unit_price', 
        'amount'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}