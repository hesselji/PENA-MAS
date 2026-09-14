<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Notification;

final class NotificationController
{
    public function index(): void
    {
        Auth::requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'count' => Notification::unreadCount(),
            'data' => Notification::all(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function read(string $id): void
    {
        Auth::requireLogin();
        Csrf::validate($_POST['_token'] ?? null);

        Notification::markAsRead((int) $id);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'count' => Notification::unreadCount(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function delete(string $id): void
    {
        Auth::requireLogin();
        Csrf::validate($_POST['_token'] ?? null);

        Notification::delete((int) $id);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'count' => Notification::unreadCount(),
        ], JSON_UNESCAPED_UNICODE);
    }
}