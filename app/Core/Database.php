<?php
namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $driver = Env::get('DB_DRIVER', 'mysql');
        if ($driver !== 'mysql') {
            throw new \RuntimeException('PENA MAS saat ini menggunakan MySQL/MariaDB.');
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $db = Env::get('DB_DATABASE', 'agenda_surat_bps');
        $user = Env::get('DB_USERNAME', 'root');
        $pass = Env::get('DB_PASSWORD', '');

        self::$pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $dbTimezone = Env::get('DB_TIMEZONE', '+07:00');
        self::$pdo->exec('SET time_zone = ' . self::$pdo->quote($dbTimezone));

        return self::$pdo;
    }
}
