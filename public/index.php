<?php
declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});

require dirname(__DIR__) . '/app/Core/helpers.php';

use App\Core\Auth;
use App\Core\Env;
use App\Core\Router;
use App\Controllers\AdminMasterController;
use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\IncomingLetterController;
use App\Controllers\NotificationController;
use App\Controllers\OutgoingLetterController;

try {
    Env::load(dirname(__DIR__) . '/.env');
} catch (Throwable $e) {
    http_response_code(500);
    exit('<h2>Konfigurasi belum siap</h2><p>Copy <code>.env.example</code> menjadi <code>.env</code> dan isi koneksi database.</p>');
}

date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Jakarta'));
if (Env::bool('APP_DEBUG', false)) ini_set('display_errors', '1');

$router = new Router();

$router->get('/', function (): void {
    header('Location: ' . (Auth::check() ? Auth::homePath() : '/login'));
    exit;
});

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/account/password', [AuthController::class, 'changePasswordForm']);
$router->post('/account/password', [AuthController::class, 'updatePassword']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/letters', [OutgoingLetterController::class, 'index']);
$router->get('/letters/create', [OutgoingLetterController::class, 'create']);
$router->post('/letters', [OutgoingLetterController::class, 'store']);
$router->get('/letters/{id}', [OutgoingLetterController::class, 'show']);
$router->post('/letters/{id}/verify-confidential', [OutgoingLetterController::class, 'verifyConfidential']);
$router->post('/letters/{id}/lock-confidential', [OutgoingLetterController::class, 'lockConfidential']);
$router->post('/letters/{id}/cancel', [OutgoingLetterController::class, 'cancel']);
$router->get('/api/classifications', [OutgoingLetterController::class, 'classifications']);
$router->get('/api/classification-groups', [OutgoingLetterController::class, 'classificationGroups']);
$router->get('/api/classification-items', [OutgoingLetterController::class, 'classificationItems']);
$router->get('/api/classification-search', [OutgoingLetterController::class, 'classificationSearch']);

$router->get('/api/notifications', [NotificationController::class, 'index']);
$router->post('/api/notifications/{id}/read', [NotificationController::class, 'read']);
$router->post('/api/notifications/{id}/delete', [NotificationController::class, 'delete']);

$router->get('/incoming', [IncomingLetterController::class, 'index']);
$router->get('/incoming/create', [IncomingLetterController::class, 'create']);
$router->post('/incoming', [IncomingLetterController::class, 'store']);
$router->get('/incoming/{id}', [IncomingLetterController::class, 'show']);

$router->get('/admin/users', [AdminUserController::class, 'index']);
$router->get('/admin/users/create', [AdminUserController::class, 'create']);
$router->post('/admin/users', [AdminUserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [AdminUserController::class, 'edit']);
$router->post('/admin/users/{id}/update', [AdminUserController::class, 'update']);
$router->get('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPasswordForm']);
$router->post('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
$router->post('/admin/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
$router->post('/admin/users/{id}/delete', [AdminUserController::class, 'delete']);

$router->get('/admin/numbering-rules', [AdminMasterController::class, 'numbering']);
$router->post('/admin/numbering-rules/{id}', [AdminMasterController::class, 'updateRule']);
$router->post('/admin/letter-types/{id}', [AdminMasterController::class, 'updateLetterType']);
$router->get('/admin/classifications', [AdminMasterController::class, 'classifications']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
