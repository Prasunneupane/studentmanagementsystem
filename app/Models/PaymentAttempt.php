<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentAttempt extends Model
{
    protected $table = 'tbl_payment_attempts';

    protected $fillable = [
        'uuid', 'invoice_id', 'gateway', 'amount', 'currency', 'status',
        'provider_transaction_id', 'provider_reference', 'checkout_url',
        'qr_payload', 'expires_at', 'verified_at', 'created_by', 'meta',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attempt) {
            $attempt->uuid ??= (string) Str::uuid();
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
