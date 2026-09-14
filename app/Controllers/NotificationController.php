<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Notification;

final class NotificationController
{
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function validateCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? null);
        Csrf::validate($token);
    }

    public function index(): void
    {
        Auth::requireLogin();
        $limit = max(1, min(20, (int) ($_GET['limit'] ?? 8)));

        try {
            $items = Notification::recentForUser((int) Auth::id(), $limit);
            $items = array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'type' => (string) $row['type'],
                    'title' => (string) $row['title'],
                    'message' => (string) $row['message'],
                    'url' => $row['url'] ?: null,
                    'is_read' => !empty($row['read_at']),
                    'created_at' => (string) $row['created_at'],
                    'time_label' => format_date_id($row['created_at'], true),
                ];
            }, $items);

            $this->json([
                'success' => true,
                'unread_count' => Notification::unreadCount((int) Auth::id()),
                'data' => $items,
            ]);
        } catch (\Throwable $e) {
            error_log('[PENA MAS][notifications] ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Notifikasi belum dapat dimuat.',
            ], 500);
        }
    }

    public function markRead(string $id): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        Notification::markRead((int) $id, (int) Auth::id());
        $this->json([
            'success' => true,
            'unread_count' => Notification::unreadCount((int) Auth::id()),
        ]);
    }

    public function markAllRead(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        Notification::markAllRead((int) Auth::id());
        $this->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    public function clear(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        Notification::clearAll((int) Auth::id());
        $this->json([
            'success' => true,
            'unread_count' => 0,
            'data' => [],
        ]);
    }
}
