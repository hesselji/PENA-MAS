<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\MasterData;
use App\Models\User;

final class AdminUserController
{
    private const ROLES = ['ADMIN','USER','INCOMING'];

    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/users/index', ['users' => User::all()]);
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('admin/users/create', ['teams' => MasterData::workTeams()]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'USER';
        $team = (int) ($_POST['work_team_id'] ?? 0);
        $errors = [];

        if (mb_strlen($name) < 3) $errors['name'] = 'Nama minimal 3 karakter.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email tidak valid.';
        elseif (User::emailExists($email)) $errors['email'] = 'Email sudah digunakan oleh akun lain.';
        if (strlen($password) < 8) $errors['password'] = 'Password minimal 8 karakter.';
        if (strlen($password) > 72) $errors['password'] = 'Password maksimal 72 karakter.';
        if (!in_array($role, self::ROLES, true)) $errors['role'] = 'Role tidak valid.';

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            unset($_SESSION['old']['password']);
            header('Location: /admin/users/create');
            exit;
        }

        try {
            $id = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'work_team_id' => $team,
            ]);
            AuditLog::write(Auth::id(), 'CREATE_USER', 'user', $id, ['email' => $email, 'role' => $role]);
            $_SESSION['flash_success'] = 'Pengguna berhasil dibuat.';
            header('Location: /admin/users');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal membuat pengguna.';
            header('Location: /admin/users/create');
            exit;
        }
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();
        $user = User::findForAdmin((int) $id);
        if (!$user) {
            http_response_code(404); View::render('errors/404'); return;
        }
        View::render('admin/users/edit', ['user'=>$user,'teams'=>MasterData::workTeams()]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        $userId = (int) $id;
        $user = User::findForAdmin($userId);
        if (!$user) {
            $_SESSION['flash_error']='Pengguna tidak ditemukan.'; header('Location:/admin/users'); exit;
        }

        $name=trim($_POST['name']??'');
        $email=strtolower(trim($_POST['email']??''));
        $role=$_POST['role']??'USER';
        $team=(int)($_POST['work_team_id']??0);
        $errors=[];
        if (mb_strlen($name)<3) $errors['name']='Nama minimal 3 karakter.';
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors['email']='Email tidak valid.';
        elseif (User::emailExists($email,$userId)) $errors['email']='Email sudah digunakan oleh akun lain.';
        if (!in_array($role,self::ROLES,true)) $errors['role']='Role tidak valid.';
        if ($userId===Auth::id() && $role!==$user['role']) $errors['role']='Role akun yang sedang Anda gunakan tidak dapat diubah.';
        if ($user['role']==='ADMIN' && $role!=='ADMIN' && (int)$user['is_active']===1 && User::activeAdminCount()<=1) {
            $errors['role']='Admin terakhir tidak dapat diubah ke role lain.';
        }

        if ($errors) {
            $_SESSION['errors']=$errors; $_SESSION['old']=$_POST;
            header('Location:/admin/users/'.$userId.'/edit'); exit;
        }

        User::updateProfile($userId,['name'=>$name,'email'=>$email,'role'=>$role,'work_team_id'=>$team]);
        AuditLog::write(Auth::id(),'UPDATE_USER','user',$userId,[
            'old_email'=>$user['email'],'new_email'=>$email,'old_role'=>$user['role'],'new_role'=>$role,
        ]);
        $_SESSION['flash_success']='Data pengguna berhasil diperbarui.';
        header('Location:/admin/users'); exit;
    }

    public function resetPasswordForm(string $id): void
    {
        Auth::requireAdmin();
        $userId=(int)$id;
        $user=User::findForAdmin($userId);
        if (!$user) { http_response_code(404); View::render('errors/404'); return; }
        if ($userId === Auth::id()) {
            $_SESSION['flash_error']='Gunakan menu Ganti Password untuk akun Admin yang sedang dipakai.';
            header('Location:/account/password');
            exit;
        }
        View::render('admin/users/reset-password',['user'=>$user]);
    }

    public function resetPassword(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token']??null);
        $userId=(int)$id;
        $user=User::findForAdmin($userId);
        if (!$user) { $_SESSION['flash_error']='Pengguna tidak ditemukan.'; header('Location:/admin/users'); exit; }
        if ($userId === Auth::id()) {
            $_SESSION['flash_error']='Untuk akun yang sedang digunakan, gunakan menu Ganti Password agar password lama tetap diverifikasi.';
            header('Location:/account/password');
            exit;
        }

        $password=$_POST['password']??'';
        $confirm=$_POST['password_confirmation']??'';
        $errors=[];
        if (strlen($password)<8) $errors['password']='Password baru minimal 8 karakter.';
        elseif (strlen($password)>72) $errors['password']='Password baru maksimal 72 karakter.';
        if ($confirm!==$password) $errors['password_confirmation']='Konfirmasi password tidak sama.';
        if ($errors) {
            $_SESSION['errors']=$errors;
            header('Location:/admin/users/'.$userId.'/reset-password'); exit;
        }

        User::adminResetPassword($userId,$password);
        AuditLog::write(Auth::id(),'RESET_USER_PASSWORD','user',$userId,['email'=>$user['email']]);
        $_SESSION['flash_success']='Password pengguna berhasil diatur ulang.';
        header('Location:/admin/users'); exit;
    }

    public function toggleStatus(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        $userId=(int)$id;
        $user=User::findForAdmin($userId);
        if (!$user) { $_SESSION['flash_error']='Pengguna tidak ditemukan.'; header('Location:/admin/users'); exit; }
        $newActive=!((int)$user['is_active']===1);
        if ($userId===Auth::id() && !$newActive) {
            $_SESSION['flash_error']='Anda tidak dapat membekukan akun yang sedang digunakan.'; header('Location:/admin/users'); exit;
        }
        if (!$newActive && $user['role']==='ADMIN' && User::activeAdminCount()<=1) {
            $_SESSION['flash_error']='Admin terakhir tidak dapat dibekukan.'; header('Location:/admin/users'); exit;
        }
        User::setActive($userId,$newActive);
        AuditLog::write(Auth::id(),$newActive?'ACTIVATE_USER':'FREEZE_USER','user',$userId,['email'=>$user['email']]);
        $_SESSION['flash_success']=$newActive?'Akun berhasil diaktifkan kembali.':'Akun berhasil dibekukan.';
        header('Location:/admin/users'); exit;
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        $userId=(int)$id;
        $user=User::findForAdmin($userId);
        if (!$user) { $_SESSION['flash_error']='Pengguna tidak ditemukan.'; header('Location:/admin/users'); exit; }
        if ($userId===Auth::id()) {
            $_SESSION['flash_error']='Anda tidak dapat menghapus akun yang sedang digunakan.'; header('Location:/admin/users'); exit;
        }
        if ($user['role']==='ADMIN' && (int)$user['is_active']===1 && User::activeAdminCount()<=1) {
            $_SESSION['flash_error']='Admin terakhir tidak dapat dihapus.'; header('Location:/admin/users'); exit;
        }
        $originalEmail=$user['email'];
        User::softDelete($userId);
        AuditLog::write(Auth::id(),'DELETE_USER','user',$userId,[
            'email'=>$originalEmail,'role'=>$user['role'],'email_reusable'=>true,
        ]);
        $_SESSION['flash_success']='Pengguna berhasil dihapus. Email lama dapat digunakan kembali dan riwayat tetap tersimpan.';
        header('Location:/admin/users'); exit;
    }
}
