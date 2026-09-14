<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});

use App\Core\Database;
use App\Core\Env;

$root = dirname(__DIR__);
Env::load($root . '/.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Jakarta'));

function ask(string $label): string {
    $value = readline($label . ': ');
    return trim((string)$value);
}

function askPassword(string $label): string {
    if (DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
        @shell_exec('stty -echo');
        fwrite(STDOUT, $label . ': ');
        $value = trim((string)fgets(STDIN));
        @shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
        return $value;
    }
    return ask($label);
}

$name = ask('Nama administrator');
$email = strtolower(ask('Email administrator'));
$password = askPassword('Password minimal 8 karakter');
$confirm = askPassword('Ulangi password');

if (mb_strlen($name) < 3) exit("Nama minimal 3 karakter.\n");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit("Email tidak valid.\n");
if (strlen($password) < 8 || strlen($password) > 72) exit("Password harus 8 sampai 72 karakter.\n");
if ($password !== $confirm) exit("Konfirmasi password tidak sama.\n");

$pdo = Database::connection();
$check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email=? AND deleted_at IS NULL');
$check->execute([$email]);
if ((int)$check->fetchColumn() > 0) exit("Email sudah digunakan.\n");

$teamId = $pdo->query('SELECT id FROM work_teams WHERE is_active=1 ORDER BY id LIMIT 1')->fetchColumn();
$stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash,role,work_team_id,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())');
$stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), 'ADMIN', $teamId ?: null]);

echo "Administrator PENA MAS berhasil dibuat.\n";
