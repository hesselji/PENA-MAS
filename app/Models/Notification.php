<?php

namespace App\Models;

use App\Core\Database;

final class Notification
{
    /**
     * Membuat notifikasi untuk semua user aktif
     * ketika sebuah surat BIASA berhasil dibuat.
     */
    public static function createForLetter(int $letterId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE is_active = 1
               AND deleted_at IS NULL"
        );

        $stmt->execute();

        $users = $stmt->fetchAll();

        if (!$users) {
            return;
        }

        $insert = $pdo->prepare(
            "INSERT IGNORE INTO notifications
             (user_id, letter_id, is_read, is_deleted, created_at)
             VALUES (?, ?, 0, 0, NOW())"
        );

        foreach ($users as $user) {
            $insert->execute([
                (int) $user['id'],
                $letterId
            ]);
        }
    }

    /**
     * Mengambil notifikasi milik user yang sedang login.
     *
     * Hanya surat BIASA yang ditampilkan.
     *
     * Rahasia dan Sangat Rahasia tidak akan keluar
     * dari query ini.
     */
    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                n.id,
                n.user_id,
                n.letter_id,
                n.is_read,
                n.created_at,

                ol.letter_number,
                ol.subject,
                ol.recipient,
                ol.sensitivity,
                ol.created_at AS letter_created_at

             FROM notifications n

             INNER JOIN outgoing_letters ol
                ON ol.id = n.letter_id

             WHERE n.user_id = ?
             AND n.is_deleted = 0
             AND n.deleted_at IS NULL
             AND ol.sensitivity = 'BIASA'

             ORDER BY n.id DESC

             LIMIT 50"
        );

        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Jumlah notifikasi yang belum dibaca.
     */
    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*)
             FROM notifications n
             INNER JOIN outgoing_letters ol
                ON ol.id = n.letter_id
             WHERE n.user_id = ?
             AND n.is_deleted = 0
             AND n.deleted_at IS NULL
             AND n.is_read = 0
             AND ol.sensitivity = 'BIASA'"
        );

        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Tandai notifikasi sebagai telah dibaca.
     */
    public static function markAsRead(
        int $notificationId,
        int $userId
    ): bool {
        $stmt = Database::connection()->prepare(
            "UPDATE notifications n
             INNER JOIN outgoing_letters ol
                ON ol.id = n.letter_id

             SET
                n.is_read = 1,
                n.read_at = NOW()

             WHERE n.id = ?
               AND n.user_id = ?
               AND n.is_deleted = 0
               AND ol.sensitivity = 'BIASA'"
        );

        $stmt->execute([
            $notificationId,
            $userId
        ]);

        return $stmt->rowCount() > 0;
    }
    

    /**
     * Menghapus notifikasi dari daftar user.
     *
     * Surat asli TIDAK dihapus.
     * Hanya notifikasinya yang disembunyikan.
     */
    public static function delete(
        int $notificationId,
        int $userId
    ): bool {
        $stmt = Database::connection()->prepare(
            "UPDATE notifications
             SET
                is_deleted = 1,
                deleted_at = NOW()

             WHERE id = ?
               AND user_id = ?
               AND is_deleted = 0"
        );

        $stmt->execute([
            $notificationId,
            $userId
        ]);

        return $stmt->rowCount() > 0;
    }
    /**
 * Tandai semua notifikasi yang belum dibaca sebagai sudah dibaca.
 */
public static function markAllAsRead(int $userId): int
{
    $stmt = Database::connection()->prepare(
        "UPDATE notifications n
         INNER JOIN outgoing_letters ol
            ON ol.id = n.letter_id

         SET
            n.is_read = 1,
            n.read_at = NOW()

         WHERE n.user_id = ?
           AND n.is_deleted = 0
           AND n.deleted_at IS NULL
           AND n.is_read = 0
           AND ol.sensitivity = 'BIASA'"
    );

    $stmt->execute([$userId]);

    return $stmt->rowCount();
}
}