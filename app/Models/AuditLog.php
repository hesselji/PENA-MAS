<?php
namespace App\Models;

use App\Core\Database;

final class AuditLog
{
    public static function write(?int $userId, string $action, string $entityType, ?int $entityId, array $meta = []): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,metadata_json,ip_address,created_at) VALUES (?,?,?,?,?,?,NOW())');
        $stmt->execute([$userId,$action,$entityType,$entityId,json_encode($meta, JSON_UNESCAPED_UNICODE),$_SERVER['REMOTE_ADDR'] ?? null]);
    }
}
