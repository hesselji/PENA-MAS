<?php
namespace App\Models;

use App\Core\Database;

final class AuditLog
{
    public static function write(?int $userId, string $action, string $entityType, ?int $entityId, array $meta = []): void
    {
        $meta = self::sanitizeMeta($meta);
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_logs (user_id,action,entity_type,entity_id,metadata_json,ip_address,created_at) VALUES (?,?,?,?,?,?,NOW())'
        );
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function history(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(a.created_at) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(a.created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'a.action = ?';
            $params[] = $filters['action'];
        }

        $sql = 'SELECT a.id, a.action, a.created_at, u.name AS user_name
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT 200';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function activityTypes(): array
    {
        return [
            'LOGIN' => 'Masuk ke sistem',
            'LOGOUT' => 'Keluar dari sistem',
            'CHANGE_PASSWORD' => 'Mengubah password',
            'GENERATE_NUMBER' => 'Membuat nomor surat',
            'GENERATE_BATCH' => 'Membuat beberapa nomor surat',
            'CANCEL_NUMBER' => 'Membatalkan nomor surat',
            'CREATE_USER' => 'Membuat akun pengguna',
            'UPDATE_USER' => 'Memperbarui akun pengguna',
            'RESET_USER_PASSWORD' => 'Mereset password pengguna',
            'ACTIVATE_USER' => 'Mengaktifkan akun',
            'FREEZE_USER' => 'Membekukan akun',
            'DELETE_USER' => 'Menghapus akun pengguna',
            'VIEW_CONFIDENTIAL_LETTER' => 'Membuka surat rahasia',
            'CONFIDENTIAL_ACCESS_FAILED' => 'Gagal membuka surat rahasia',
            'CREATE_INCOMING_LETTER' => 'Mencatat surat masuk',
            'UPDATE_NUMBERING_RULE' => 'Memperbarui aturan nomor',
            'UPDATE_LETTER_TYPE_RULE' => 'Memperbarui aturan jenis surat',
        ];
    }

    private static function sanitizeMeta(array $meta): array
    {
        $blocked = [
            'password', 'password_hash', 'current_password', 'new_password',
            'confirm_password', 'token', 'csrf_token', 'secret', 'content',
            'body', 'file_content', 'document_content',
        ];

        $clean = [];
        foreach ($meta as $key => $value) {
            $keyLower = strtolower((string) $key);
            $isBlocked = false;
            foreach ($blocked as $word) {
                if ($keyLower === $word || str_contains($keyLower, $word)) {
                    $isBlocked = true;
                    break;
                }
            }
            if ($isBlocked) continue;
            $clean[$key] = is_array($value) ? self::sanitizeMeta($value) : $value;
        }
        return $clean;
    }
}
