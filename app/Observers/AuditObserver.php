<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * One observer, reused across every audited model (registered per-model in
 * AppServiceProvider::boot(), same convention as RolesObserver/PermissionObserver).
 * Writes into the existing tbl_audit_logs table — this is the data source
 * the principal-dashboard activity feed will read from later.
 */
class AuditObserver
{
    /** Keys that must never be written to old_values/new_values, regardless of model. */
    private const HIDDEN_KEY_FRAGMENTS = ['password', 'remember_token', 'token', 'secret', 'api_key'];

    public function created(Model $model): void
    {
        AuditLog::create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'actor_type' => Auth::check() ? Auth::user()->getMorphClass() : null,
            'actor_id' => Auth::id(),
            'event' => 'created',
            'old_values' => null,
            'new_values' => $this->sanitize($model->getAttributes()),
            'changed_fields' => null,
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
            'url' => Request::fullUrl(),
            'tags' => class_basename($model),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $this->sanitize($model->getChanges());
        unset($changes['updated_at']);
        if (empty($changes)) {
            return; // only a timestamp touch (e.g. touch()) — not a meaningful change
        }

        $old = $this->sanitize(array_intersect_key($model->getOriginal(), $changes));

        AuditLog::create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'actor_type' => Auth::check() ? Auth::user()->getMorphClass() : null,
            'actor_id' => Auth::id(),
            'event' => 'updated',
            'old_values' => $old,
            'new_values' => $changes,
            'changed_fields' => array_keys($changes),
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
            'url' => Request::fullUrl(),
            'tags' => class_basename($model),
        ]);
    }

    private function sanitize(array $attributes): array
    {
        foreach (array_keys($attributes) as $key) {
            foreach (self::HIDDEN_KEY_FRAGMENTS as $fragment) {
                if (str_contains(strtolower($key), $fragment)) {
                    unset($attributes[$key]);
                    break;
                }
            }
        }

        return $attributes;
    }
}
