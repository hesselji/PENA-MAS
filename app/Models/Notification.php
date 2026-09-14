<?php
namespace App\Models;

use App\Core\Database;

final class Notification
{
    public static function recentForUser(int $userId, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::connection()->prepare(
            "SELECT id,type,title,message,url,read_at,created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Penerima notifikasi surat keluar.
     * - Biasa: seluruh ADMIN + USER aktif, kecuali pembuat.
     * - Rahasia/Sangat Rahasia: hanya ADMIN aktif, kecuali pembuat.
     * Role INCOMING tidak menerima notifikasi surat keluar.
     */
    public static function recipientIdsForOutgoing(string $sensitivity, int $creatorId): array
    {
        $roles = $sensitivity === 'BIASA' ? ['ADMIN', 'USER'] : ['ADMIN'];
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $params = array_merge($roles, [$creatorId]);

        $stmt = Database::connection()->prepare(
            "SELECT id
             FROM users
             WHERE role IN ({$placeholders})
               AND is_active = 1
               AND deleted_at IS NULL
               AND id <> ?
             ORDER BY id"
        );
        $stmt->execute($params);
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    public static function createForUsers(
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): void {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn($id) => $id > 0)));
        if (!$userIds) return;

        $pdo = Database::connection();
        $startedHere = !$pdo->inTransaction();
        if ($startedHere) $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO notifications
                 (user_id,type,title,message,url,entity_type,entity_id,created_at)
                 VALUES (?,?,?,?,?,?,?,NOW())'
            );

            foreach ($userIds as $userId) {
                $stmt->execute([
                    $userId,
                    $type,
                    $title,
                    $message,
                    $url,
                    $entityType,
                    $entityId,
                ]);
            }

            if ($startedHere) $pdo->commit();
        } catch (\Throwable $e) {
            if ($startedHere && $pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function markRead(int $notificationId, int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$notificationId, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId]);
    }

    public static function clearAll(int $userId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM notifications WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
