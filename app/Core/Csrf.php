<?php
namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validate(?string $token): void
    {
        if (!$token || !hash_equals(self::token(), $token)) {
            http_response_code(419);
            exit('CSRF token tidak valid. Silakan refresh halaman.');
        }
    }
}
