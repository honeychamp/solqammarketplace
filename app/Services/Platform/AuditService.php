<?php

namespace App\Services\Platform;

use Config\Database;

class AuditService
{
    public static function log(string $action, ?string $entity = null, ?int $entityId = null, array $meta = []): void
    {
        try {
            Database::connect()->table('audit_logs')->insert([
                'user_id'    => session()->get('user.id'),
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'meta'       => $meta ? json_encode($meta) : null,
                'ip_address' => service('request')->getIPAddress(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Audit log failed: ' . $e->getMessage());
        }
    }
}
