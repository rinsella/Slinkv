<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $affectedUserId = null,
    ): void {
        $admin = Auth::user();
        $request = request();
        $ip = $request?->ip();

        AuditLog::create([
            'admin_id' => $admin?->id,
            'user_id' => $affectedUserId,
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_hash' => $ip ? hash('sha256', $ip) : null,
            'user_agent' => substr((string) $request?->userAgent(), 0, 500),
        ]);
    }

    public function diff(array $original, array $updated): array
    {
        $old = [];
        $new = [];
        foreach ($updated as $key => $value) {
            $orig = $original[$key] ?? null;
            if ($orig != $value) {
                $old[$key] = $orig;
                $new[$key] = $value;
            }
        }
        return [$old ?: null, $new ?: null];
    }
}
