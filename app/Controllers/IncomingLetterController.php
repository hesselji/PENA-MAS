<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\MasterData;
use App\Models\Notification;
use App\Models\User;

final class IncomingLetterController
{
    public function index(): void
    {
        Auth::requireIncomingAccess();
        $filters=['q'=>trim($_GET['q']??''),'year'=>$_GET['year']??date('Y'),'workflow_status'=>$_GET['workflow_status']??''];
        View::render('incoming/index',['letters'=>IncomingLetter::search($filters),'filters'=>$filters]);
    }

    public function create(): void
    {
        Auth::requireIncomingAccess();View::render('incoming/create');
    }

    public function store(): void
    {
        Auth::requireIncomingAccess();Csrf::validate($_POST['_token']??null);
        $data=[
            'letter_number'=>trim($_POST['letter_number']??''),'origin'=>trim($_POST['origin']??''),'subject'=>trim($_POST['subject']??''),
            'recipient'=>trim($_POST['recipient']??''),'letter_date'=>$_POST['letter_date']??'','received_date'=>$_POST['received_date']??date('Y-m-d'),
            'notes'=>trim($_POST['notes']??''),'created_by'=>(int)Auth::id(),
        ];
        $errors=[];foreach(['letter_number','origin','subject','recipient','letter_date','received_date'] as $field)if($data[$field]==='')$errors[$field]='Wajib diisi.';
        foreach(['letter_date','received_date'] as $field){$d=\DateTime::createFromFormat('Y-m-d',$data[$field]);if(!$d||$d->format('Y-m-d')!==$data[$field])$errors[$field]='Tanggal tidak valid.';}
        if($data['letter_date']&&$data['received_date']&&$data['letter_date']>$data['received_date'])$errors['letter_date']='Tanggal surat tidak boleh setelah tanggal diterima.';
        if($errors){$_SESSION['errors']=$errors;$_SESSION['old']=$_POST;header('Location:/incoming/create');exit;}
        try{$id=IncomingLetter::create($data);$this->handleAttachment($id);AuditLog::write(Auth::id(),'CREATE_INCOMING_LETTER','incoming_letter',$id,['letter_number'=>$data['letter_number'],'origin'=>$data['origin']]);
            unset($_SESSION['old']);$_SESSION['flash_success']='Surat masuk berhasil dicatat.';header('Location:/incoming/'.$id);exit;
        }catch(\Throwable $e){$_SESSION['flash_error']='Surat masuk belum berhasil dicatat.';$_SESSION['old']=$_POST;header('Location:/incoming/create');exit;}
    }

    public function show(string $id): void
    {
        Auth::requireIncomingAccess();$letter=IncomingLetter::find((int)$id);if(!$letter){http_response_code(404);View::render('errors/404');return;}
        View::render('incoming/show',['letter'=>$letter,'events'=>IncomingLetter::events((int)$id),'dispositions'=>IncomingLetter::dispositions((int)$id),'teams'=>MasterData::workTeams(),'assignees'=>User::incomingAssignees()]);
    }

    public function updateWorkflow(string $id): void
    {
        Auth::requireIncomingAccess();Csrf::validate($_POST['_token']??null);$letter=IncomingLetter::find((int)$id);
        if(!$letter){http_response_code(404);View::render('errors/404');return;}
        $status=$_POST['workflow_status']??'';$teams=$_POST['assigned_team_ids']??[];$teams=is_array($teams)?$teams:[];$assignedUsers=$_POST['assigned_user_by_team']??[];$assignedUsers=is_array($assignedUsers)?$assignedUsers:[];$note=trim($_POST['follow_up_note']??'');
        if(!in_array($status,['MASUK','DISPOSISI','SELESAI'],true)){$_SESSION['flash_error']='Status tindak lanjut tidak valid.';header('Location:/incoming/'.$id);exit;}
        if($status==='DISPOSISI' && !$teams){$_SESSION['flash_error']='Pilih minimal satu tim untuk disposisi.';header('Location:/incoming/'.$id);exit;}
        $cleanUsers=[];foreach($teams as $teamId){$teamId=(int)$teamId;if($teamId>0)$cleanUsers[$teamId]=(int)($assignedUsers[$teamId]??0);}
        IncomingLetter::updateWorkflow((int)$id,['workflow_status'=>$status,'assigned_teams'=>array_keys($cleanUsers),'assigned_users'=>$cleanUsers,'follow_up_note'=>$note],(int)Auth::id());
        AuditLog::write(Auth::id(),'UPDATE_INCOMING_WORKFLOW','incoming_letter',(int)$id,['status'=>$status,'assigned_team_ids'=>array_keys($cleanUsers)]);
        $notify=[];foreach($cleanUsers as $uid){if($uid>0 && $uid!==(int)Auth::id())$notify[]=$uid;}
        if($notify){try{Notification::createForUsers(array_values(array_unique($notify)),'INCOMING_ASSIGNED','Disposisi surat masuk','Anda ditetapkan sebagai penerima disposisi surat '.$letter['letter_number'].'.','/incoming/'.$id,'incoming_letter',(int)$id);}catch(\Throwable $e){error_log('[PENA MAS][incoming notification] '.$e->getMessage());}}
        $_SESSION['flash_success']='Tindak lanjut surat masuk diperbarui.';header('Location:/incoming/'.$id);exit;
    }

    public function attachment(string $id): void
    {
        Auth::requireIncomingAccess();$letter=IncomingLetter::find((int)$id);if(!$letter||empty($letter['attachment_path'])){http_response_code(404);exit('Lampiran tidak ditemukan.');}
        $path=dirname(__DIR__,2).'/'.ltrim((string)$letter['attachment_path'],'/');if(!is_file($path)){http_response_code(404);exit('Lampiran tidak ditemukan.');}
        header('Content-Type: '.($letter['attachment_mime']?:'application/octet-stream'));header('Content-Length: '.filesize($path));header('Content-Disposition: inline; filename="'.basename((string)$letter['attachment_name']).'"');readfile($path);exit;
    }
    private function handleAttachment(int $id): void
    {
        $f=$_FILES['attachment']??null;if(!$f||($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return;if(($f['error']??1)!==UPLOAD_ERR_OK)throw new \RuntimeException('Lampiran gagal diunggah.');if(($f['size']??0)>8*1024*1024)throw new \RuntimeException('Lampiran maksimal 8 MB.');$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$allowed=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];if(!isset($allowed[$mime]))throw new \RuntimeException('Lampiran hanya PDF, JPG, atau PNG.');$dir=dirname(__DIR__,2).'/storage/uploads/incoming';if(!is_dir($dir))mkdir($dir,0775,true);$name=$id.'-'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new \RuntimeException('Lampiran belum dapat disimpan.');IncomingLetter::attach($id,['name'=>$f['name'],'path'=>'storage/uploads/incoming/'.$name,'mime'=>$mime,'size'=>(int)$f['size']]);
    }

}
