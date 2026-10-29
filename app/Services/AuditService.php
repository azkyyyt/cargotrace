<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(string $action, $auditable, ?array $oldValues = null, ?array $newValues = null, ?User $actor = null): AuditLog
    {
        $userId = $actor ? $actor->id : (auth()->check() ? auth()->id() : null);

        return AuditLog::create([
            'user_id' => $userId,
            'action' => strtoupper($action),
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'Console/API',
            'created_at' => now(),
        ]);
    }
}
