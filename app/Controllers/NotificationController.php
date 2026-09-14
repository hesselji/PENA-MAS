<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Notification;

final class NotificationController
{
    /**
     * GET /api/notifications
     */
    public function index(): void
    {
        Auth::requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        $userId = Auth::id();

        $notifications = Notification::forUser($userId);
        $count = Notification::unreadCount($userId);

        echo json_encode([
            'success' => true,
            'count' => $count,
            'data' => $notifications,
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    /**
     * POST /api/notifications/{id}/read
     */
    public function read(string $id): void
    {
        Auth::requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        try {
            Csrf::validate($_POST['_token'] ?? null);

            $notificationId = (int) $id;
            $userId = Auth::id();

            if ($notificationId <= 0) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' => 'ID notifikasi tidak valid.'
                ]);

                exit;
            }

            Notification::markAsRead(
                $notificationId,
                $userId
            );

            $count = Notification::unreadCount($userId);

            echo json_encode([
                'success' => true,
                'count' => $count,
            ]);

        } catch (\Throwable $e) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        exit;
    }
    
    /**
 * POST /api/notifications/read-all
 */
public function readAll(): void
{
    Auth::requireLogin();

    header('Content-Type: application/json; charset=utf-8');

    try {
        Csrf::validate($_POST['_token'] ?? null);

        $userId = Auth::id();

        $updated = Notification::markAllAsRead($userId);

        $count = Notification::unreadCount($userId);

        echo json_encode([
            'success' => true,
            'updated' => $updated,
            'count' => $count,
        ], JSON_UNESCAPED_UNICODE);

    } catch (\Throwable $e) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }

    exit;
}

    /**
     * POST /api/notifications/{id}/delete
     */
    public function delete(string $id): void
    {
        Auth::requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        try {
            Csrf::validate($_POST['_token'] ?? null);

            $notificationId = (int) $id;
            $userId = Auth::id();

            if ($notificationId <= 0) {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' => 'ID notifikasi tidak valid.'
                ]);

                exit;
            }

            Notification::delete(
                $notificationId,
                $userId
            );

            $count = Notification::unreadCount($userId);

            echo json_encode([
                'success' => true,
                'count' => $count,
            ]);

        } catch (\Throwable $e) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        exit;
    }
}