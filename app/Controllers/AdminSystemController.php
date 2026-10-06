<?php
namespace App\Controllers;

use App\Core\Auth;use App\Core\Csrf;use App\Core\View;use App\Models\AuditLog;use App\Services\BackupService;

final class AdminSystemController
{
    public function index(): void{Auth::requireAdmin();View::render('admin/system/index');}
    public function backup(): void{Auth::requireAdmin();$data=BackupService::export();AuditLog::write(Auth::id(),'CREATE_BACKUP','system',null);header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="PENA-MAS-backup-'.date('Ymd-His').'.json"');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;}
    public function restore(): void{Auth::requireAdmin();Csrf::validate($_POST['_token']??null);$user=Auth::user();$password=(string)($_POST['current_password']??'');if(!$user||!password_verify($password,(string)$user['password_hash'])){$_SESSION['flash_error']='Password Administrator tidak sesuai.';header('Location:/admin/system');exit;}if(($_POST['confirmation']??'')!=='PULIHKAN'){$_SESSION['flash_error']='Ketik PULIHKAN untuk mengonfirmasi.';header('Location:/admin/system');exit;}$f=$_FILES['backup_file']??null;if(!$f||($f['error']??1)!==UPLOAD_ERR_OK||($f['size']??0)>20*1024*1024){$_SESSION['flash_error']='Berkas backup tidak valid atau terlalu besar.';header('Location:/admin/system');exit;}$payload=json_decode((string)file_get_contents($f['tmp_name']),true);try{BackupService::restore(is_array($payload)?$payload:[]);$_SESSION['flash_success']='Backup berhasil dipulihkan. Silakan login kembali.';Auth::logout();header('Location:/login');exit;}catch(\Throwable $e){error_log('[PENA MAS][restore] '.$e->getMessage());$_SESSION['flash_error']='Pemulihan belum berhasil. Pastikan berkas berasal dari PENA MAS versi ini.';header('Location:/admin/system');exit;}}
}