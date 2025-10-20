<?php
namespace System\Services\Admin;

use System\Core\Auth;
use System\Core\DB;

class AuditLogger
{
    public static function log(string $action, string $entity, ?int $entityId = null, array $details = []): void
    {
        try {
            DB::query('INSERT INTO logs (user_id, action, entity, entity_id, ip, user_agent, details) VALUES (:user_id,:action,:entity,:entity_id,:ip,:ua,:details)', [
                'user_id' => Auth::id(),
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 250),
                'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable $e) {
            // swallowing logging errors to avoid breaking the main flow
        }
    }
}
