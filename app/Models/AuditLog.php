<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'tbl_audit_logs';

    // The table only has created_at (append-only log, see its migration) —
    // Eloquent must not try to write a nonexistent updated_at column.
    const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type', 'auditable_id',
        'actor_type', 'actor_id',
        'event', 'old_values', 'new_values', 'changed_fields',
        'ip_address', 'user_agent', 'url', 'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    public function auditable()
    {
        return $this->morphTo();
    }

    public function actor()
    {
        return $this->morphTo();
    }
}
