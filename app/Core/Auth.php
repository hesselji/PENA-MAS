<?php
namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function user(): ?array
    {
        $id = self::id();
        return $id ? User::find($id) : null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        $id = self::id();
        return $id !== null && User::find($id) !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'ADMIN';
    }

    public static function isUser(): bool
    {
        return self::role() === 'USER';
    }

    public static function isIncoming(): bool
    {
        return self::role() === 'INCOMING';
    }

    public static function canAccessOutgoing(): bool
    {
        return in_array(self::role(), ['ADMIN', 'USER'], true);
    }

    public static function canAccessIncoming(): bool
    {
        return in_array(self::role(), ['ADMIN', 'INCOMING'], true);
    }

    public static function homePath(): string
    {
        return self::isIncoming() ? '/incoming' : '/dashboard';
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        unset($_SESSION['confidential_grants']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        $id = self::id();
        if ($id === null) {
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
            header('Location: /login');
            exit;
        }

        if (User::find($id) === null) {
            unset($_SESSION['user_id']);
            $_SESSION['flash_error'] = 'Akun Anda sedang dinonaktifkan atau tidak lagi tersedia. Hubungi administrator.';
            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    public static function requireOutgoingAccess(): void
    {
        self::requireLogin();
        if (!self::canAccessOutgoing()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    public static function requireIncomingAccess(): void
    {
        self::requireLogin();
        if (!self::canAccessIncoming()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    public static function grantConfidentialAccess(int $letterId, int $ttlSeconds = 300): void
    {
        $_SESSION['confidential_grants'] ??= [];
        $_SESSION['confidential_grants'][(string) $letterId] = time() + max(30, $ttlSeconds);
    }

    public static function hasConfidentialGrant(int $letterId): bool
    {
        if (!self::isAdmin()) return false;
        $expires = (int) ($_SESSION['confidential_grants'][(string) $letterId] ?? 0);
        if ($expires < time()) {
            unset($_SESSION['confidential_grants'][(string) $letterId]);
            return false;
        }
        return true;
    }

    public static function clearConfidentialGrant(int $letterId): void
    {
        unset($_SESSION['confidential_grants'][(string) $letterId]);
    }
}
