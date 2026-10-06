<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\User;

final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            header('Location: ' . Auth::homePath());
            exit;
        }
        View::render('auth/login');
    }

    public function login(): void
    {
        Csrf::validate($_POST['_token'] ?? null);
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $user = User::byEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Email atau password salah.';
            $_SESSION['old'] = ['email' => $email];
            header('Location: /login');
            exit;
        }

        Auth::login($user);
        AuditLog::write((int) $user['id'], 'LOGIN', 'user', (int) $user['id']);
        header('Location: ' . Auth::homePath());
        exit;
    }

    public function changePasswordForm(): void
    {
        Auth::requireLogin();
        View::render('auth/change-password');
    }

    public function updatePassword(): void
    {
        Auth::requireLogin();
        Csrf::validate($_POST['_token'] ?? null);

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['new_password_confirmation'] ?? '';
        $errors = [];
        $userId = Auth::id();
        $user = $userId ? User::find($userId) : null;

        if (!$userId || !$user) {
            Auth::logout();
            header('Location: /login');
            exit;
        }

        if ($currentPassword === '') {
            $errors['current_password'] = 'Password saat ini wajib diisi.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errors['current_password'] = 'Password saat ini tidak sesuai.';
        }

        if ($newPassword === '') {
            $errors['new_password'] = 'Password baru wajib diisi.';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password baru minimal 8 karakter.';
        } elseif (strlen($newPassword) > 72) {
            $errors['new_password'] = 'Password baru maksimal 72 karakter.';
        } elseif (password_verify($newPassword, $user['password_hash'])) {
            $errors['new_password'] = 'Password baru harus berbeda dari password saat ini.';
        }

        if ($confirmPassword === '') {
            $errors['new_password_confirmation'] = 'Konfirmasi password wajib diisi.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['new_password_confirmation'] = 'Konfirmasi password baru tidak sama.';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            header('Location: /account/password');
            exit;
        }

        User::updatePassword($userId, $newPassword);
        AuditLog::write($userId, 'CHANGE_PASSWORD', 'user', $userId);
        session_regenerate_id(true);
        unset($_SESSION['confidential_grants']);
        $_SESSION['flash_success'] = 'Password berhasil diperbarui.';
        header('Location: /account/password');
        exit;
    }

    public function logout(): void
    {
        Csrf::validate($_POST['_token'] ?? null);
        $id = Auth::id();
        if ($id) AuditLog::write($id, 'LOGOUT', 'user', $id);
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
