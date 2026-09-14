<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Notification
{
    public static function all(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                ol.id,
                ol.letter_number,
                ol.subject,
                ol.recipient,
                ol.created_at,
                ol.status,
                COALESCE(ns.is_read, 0) AS is_read
             FROM outgoing_letters ol
             LEFT JOIN notification_states ns
                ON ns.outgoing_letter_id = ol.id
                AND ns.user_id = ?
             WHERE ol.sensitivity = 'BIASA'
               AND (ns.deleted_at IS NULL)
             ORDER BY ol.id DESC
             LIMIT 50"
        );

        $stmt->execute([(int) Auth::id()]);

        return $stmt->fetchAll();
    }

    public static function unreadCount(): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*)
             FROM outgoing_letters ol
             LEFT JOIN notification_states ns
                ON ns.outgoing_letter_id = ol.id
                AND ns.user_id = ?
             WHERE ol.sensitivity = 'BIASA'
               AND COALESCE(ns.is_read, 0) = 0
               AND ns.deleted_at IS NULL"
        );

        $stmt->execute([(int) Auth::id()]);

        return (int) $stmt->fetchColumn();
    }

    public static function markAsRead(int $letterId): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO notification_states
                (user_id, outgoing_letter_id, is_read)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE
                is_read = 1,
                deleted_at = NULL"
        );

        $stmt->execute([
            (int) Auth::id(),
            $letterId
        ]);
    }

    public static function delete(int $letterId): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO notification_states
                (user_id, outgoing_letter_id, is_read, deleted_at)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                deleted_at = NOW()"
        );

        $stmt->execute([
            (int) Auth::id(),
            $letterId
        ]);
    }
}