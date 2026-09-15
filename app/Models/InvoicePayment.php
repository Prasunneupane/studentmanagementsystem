<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $table = 'tbl_invoice_payments';

    protected $fillable = [
        'invoice_id', 'amount', 'paid_on',
        'payment_method', 'reference_no',
        'note', 'received_by','payment_status'
      ];

    protected $casts = [
        'paid_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}