<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\MasterData;
use App\Models\Notification;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterNumberGenerator;


final class OutgoingLetterController
{
    public function index(): void
    {
        Auth::requireOutgoingAccess();
        $filters=[
            'q'=>trim($_GET['q']??''),
            'team'=>$_GET['team']??'',
            'sensitivity'=>Auth::isAdmin() ? ($_GET['sensitivity']??'') : 'BIASA',
            'status'=>$_GET['status']??'',
            'year'=>$_GET['year']??date('Y'),
        ];
        View::render('letters/index',[
            'letters'=>OutgoingLetter::search($filters),
            'filters'=>$filters,
            'teams'=>MasterData::workTeams(),
            'sensitivities'=>Auth::isAdmin() ? MasterData::sensitivities() : [['code'=>'BIASA','name'=>'Biasa','prefix'=>'B']],
        ]);
    }

    public function create(): void
    {
        Auth::requireOutgoingAccess();
        View::render('letters/create',[
            'teams'=>MasterData::workTeams(),
            'types'=>MasterData::letterTypes(),
            'sensitivities'=>MasterData::sensitivities(),
            'archiveTypes'=>MasterData::archiveTypes(),
            'recipientCandidates'=>User::recipientCandidates(),
        ]);
    }

    public function store(): void
    {
        Auth::requireOutgoingAccess();
        Csrf::validate($_POST['_token']??null);

        $isAdmin = Auth::isAdmin();
        $input=[
            'letter_type_id'=>(int)($_POST['letter_type_id']??0),
            'work_team_id'=>(int)($_POST['work_team_id']??0),
            'system_type'=>$_POST['system_type']??'',
            'letter_date'=>$_POST['letter_date']??'',
            'sensitivity'=>$isAdmin ? ($_POST['sensitivity']??'') : 'BIASA',
            'uses_budget'=>$_POST['uses_budget']??'',
            'archive_type'=>$_POST['archive_type']??'',
            'classification_parent'=>strtoupper(trim($_POST['classification_parent']??'')),
            'classification_child'=>trim($_POST['classification_child']??''),
            'subject'=>trim($_POST['subject']??''),
            'notes'=>trim($_POST['notes']??''),
            'requested_by'=>Auth::id(),
        ];

        // Requirement Pak Citra: ada anggaran => FASILITATIF + KU otomatis.
        if ($input['uses_budget'] === 'Y') {
            $input['archive_type'] = 'FASILITATIF';
            $input['classification_parent'] = 'KU';
        }

        // Tujuan dapat berasal dari: input manual, paste banyak nama, dan akun aktif PENA MAS.
        // Semua sumber digabung, dirapikan, lalu di-deduplicate tanpa mengubah urutan pertama.
        $recipients = [];
        $manualRecipient = trim($_POST['recipient'] ?? '');
        if ($manualRecipient !== '') $recipients[] = $manualRecipient;

        $selectedUserIds = $_POST['selected_user_ids'] ?? [];
        if (!is_array($selectedUserIds)) $selectedUserIds = [];
        foreach (User::recipientNamesByIds($selectedUserIds) as $name) {
            $recipients[] = $name;
        }

        $bulkText = trim($_POST['recipients_bulk'] ?? '');
        if ($bulkText !== '') {
            $lines = preg_split('/\R/u', $bulkText) ?: [];
            foreach ($lines as $line) {
                $line = trim(preg_replace('/\s+/u', ' ', $line) ?? $line);
                // Mendukung hasil copy list bernomor sederhana: "1. Nama" / "1) Nama".
                $line = preg_replace('/^\s*\d+\s*[.\-)]\s*/u', '', $line) ?? $line;
                if ($line !== '') $recipients[] = $line;
            }
        }

        $deduped = [];
        $seen = [];
        foreach ($recipients as $recipient) {
            $recipient = trim(preg_replace('/\s+/u', ' ', (string)$recipient) ?? (string)$recipient);
            if ($recipient === '') continue;
            $key = function_exists('mb_strtolower') ? mb_strtolower($recipient, 'UTF-8') : strtolower($recipient);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $deduped[] = $recipient;
        }
        $recipients = $deduped;

        $errors=[];
        foreach([
            'letter_type_id','work_team_id','system_type','letter_date','sensitivity','uses_budget',
            'archive_type','classification_parent','classification_child','subject'
        ] as $field) {
            if (empty($input[$field])) $errors[$field]='Wajib diisi.';
        }

        if (!$recipients) $errors['recipients']='Minimal satu tujuan surat wajib diisi atau dipilih.';
        if (count($recipients) > 200) $errors['recipients']='Maksimal 200 tujuan dalam satu kali generate.';
        foreach ($recipients as $recipient) {
            $recipientLength = function_exists('mb_strlen') ? mb_strlen($recipient, 'UTF-8') : strlen($recipient);
            if ($recipientLength > 255) {
                $errors['recipients']='Setiap tujuan maksimal 255 karakter.';
                break;
            }
        }

        if(!in_array($input['system_type'],['SRIKANDI','NON_SRIKANDI'],true)) $errors['system_type']='Pilihan tidak valid.';
        if(!in_array($input['uses_budget'],['Y','T'],true)) $errors['uses_budget']='Pilihan tidak valid.';
        if(!in_array($input['archive_type'],['FASILITATIF','SUBSTANTIF'],true)) $errors['archive_type']='Pilihan tidak valid.';

        $allowedSensitivity = $isAdmin ? ['BIASA','RAHASIA','SANGAT_RAHASIA'] : ['BIASA'];
        if (!in_array($input['sensitivity'], $allowedSensitivity, true)) {
            $errors['sensitivity'] = 'Sifat surat tidak diizinkan untuk akun ini.';
        }

        $type = $input['letter_type_id'] ? MasterData::letterType($input['letter_type_id']) : null;
        if (!$type) {
            $errors['letter_type_id'] = 'Jenis surat atau aturan nomor tidak aktif.';
        } else {
            // 62710: tanggal H- tidak boleh. Hari ini dan tanggal setelahnya diperbolehkan.
            if ($type['rule_code'] === 'MAIN_62710' && $input['letter_date'] && $input['letter_date'] < date('Y-m-d')) {
                $errors['letter_date'] = 'Untuk jalur 62710, tanggal surat tidak boleh sebelum hari ini.';
            }
        }

        $dateObj = \DateTime::createFromFormat('Y-m-d', $input['letter_date']);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $input['letter_date']) {
            $errors['letter_date'] = 'Tanggal surat tidak valid.';
        }

        $input['scope_key']=$input['uses_budget'].($input['archive_type']==='FASILITATIF'?'F':'S');

        if(
            $input['archive_type'] && $input['classification_parent'] && $input['classification_child'] &&
            !MasterData::validClassification($input['archive_type'],$input['classification_parent'],$input['classification_child'])
        ) {
            $errors['classification_child']='Kode klasifikasi tidak sesuai jenis arsip atau kelompok KKA yang dipilih.';
        }

        if ($errors) {
            $_SESSION['errors']=$errors;
            $_SESSION['old']=$_POST;
            $_SESSION['old']['sensitivity']=$input['sensitivity'];
            $_SESSION['old']['archive_type']=$input['archive_type'];
            $_SESSION['old']['classification_parent']=$input['classification_parent'];
            header('Location:/letters/create');
            exit;
        }

        try {
            $results=(new LetterNumberGenerator())->generateBatch($input,$recipients);
$count=count($results);
$first=$results[0];
$last=$results[$count-1];

/*
 * Buat notifikasi hanya untuk surat BIASA.
 *
 * RAHASIA dan SANGAT_RAHASIA tidak dibuatkan
 * notifikasi sama sekali.
 */
if ($input['sensitivity'] === 'BIASA') {
    foreach ($results as $result) {
        Notification::createForLetter(
            (int) $result['id']
        );
    }
}

            AuditLog::write(Auth::id(),$count > 1 ? 'GENERATE_BATCH' : 'GENERATE_NUMBER','outgoing_letter',$first['id'],[
                'count'=>$count,
                'first_letter_number'=>$first['letter_number'],
                'last_letter_number'=>$last['letter_number'],
                'rule_code'=>$first['rule_code'],
                'sensitivity'=>$input['sensitivity'],
                'letter_ids'=>array_column($results,'id'),
            ]);

            unset($_SESSION['old']);
            if ($count === 1) {
                $_SESSION['flash_success']='Nomor surat berhasil di-generate: '.$first['letter_number'];
                header('Location:/letters/'.$first['id']);
            } else {
                $_SESSION['flash_success']=$count.' nomor surat berhasil di-generate sekaligus: '.$first['letter_number'].' s.d. '.$last['letter_number'];
                header('Location:/letters');
            }
            exit;
        } catch(\Throwable $e) {
            $_SESSION['flash_error']='Gagal generate nomor: '.$e->getMessage();
            $_SESSION['old']=$_POST;
            header('Location:/letters/create');
            exit;
        }
    }

    public function show(string $id): void
    {
        Auth::requireOutgoingAccess();
        $letter=OutgoingLetter::find((int)$id);
        if(!$letter) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        if (OutgoingLetter::isConfidential($letter)) {
            if (!Auth::isAdmin()) {
                http_response_code(404);
                View::render('errors/404');
                return;
            }
            if (!Auth::hasConfidentialGrant((int)$letter['id'])) {
                View::render('letters/confidential-lock',['letter'=>$letter]);
                return;
            }
        }

        View::render('letters/show',['letter'=>$letter]);
    }

    public function verifyConfidential(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token']??null);
        $letter = OutgoingLetter::findRaw((int)$id);
        if (!$letter || !OutgoingLetter::isConfidential($letter)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $password = $_POST['password'] ?? '';
        $admin = User::findForAdmin((int)Auth::id());
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            AuditLog::write(Auth::id(),'CONFIDENTIAL_ACCESS_FAILED','outgoing_letter',(int)$id);
            $_SESSION['flash_error']='Password tidak sesuai. Akses surat rahasia ditolak.';
            header('Location:/letters/'.$id);
            exit;
        }

        $ttl = max(30, (int)Env::get('CONFIDENTIAL_REAUTH_SECONDS', '300'));
        Auth::grantConfidentialAccess((int)$id, $ttl);
        AuditLog::write(Auth::id(),'VIEW_CONFIDENTIAL_LETTER','outgoing_letter',(int)$id,[
            'sensitivity'=>$letter['sensitivity'],
        ]);
        $_SESSION['flash_success']='Verifikasi berhasil. Detail surat rahasia dibuka sementara.';
        header('Location:/letters/'.$id);
        exit;
    }

    public function lockConfidential(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token']??null);
        Auth::clearConfidentialGrant((int)$id);
        header('Location:/letters/'.$id);
        exit;
    }

    public function cancel(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token']??null);
        $letter = OutgoingLetter::findRaw((int)$id);
        if (!$letter) {
            http_response_code(404); View::render('errors/404'); return;
        }
        if (OutgoingLetter::isConfidential($letter) && !Auth::hasConfidentialGrant((int)$id)) {
            $_SESSION['flash_error']='Verifikasi password terlebih dahulu untuk mengelola surat rahasia.';
            header('Location:/letters/'.$id);
            exit;
        }

        $reason=trim($_POST['reason']??'');
        if(strlen($reason)<5) {
            $_SESSION['flash_error']='Alasan pembatalan minimal 5 karakter.';
            header('Location:/letters/'.$id);
            exit;
        }
        OutgoingLetter::cancel((int)$id,(int)Auth::id(),$reason);
        AuditLog::write(Auth::id(),'CANCEL_NUMBER','outgoing_letter',(int)$id,['reason'=>$reason]);
        $_SESSION['flash_success']='Nomor ditandai DIBATALKAN dan tetap tersimpan sebagai audit trail.';
        header('Location:/letters/'.$id);
        exit;
    }

    public function classificationGroups(): void
    {
        Auth::requireOutgoingAccess();
        header('Content-Type: application/json; charset=utf-8');
        $archiveType=$_GET['archive_type']??'';
        if(!in_array($archiveType,['FASILITATIF','SUBSTANTIF'],true)) {
            http_response_code(422); echo json_encode(['success'=>false,'message'=>'Jenis arsip tidak valid']); return;
        }
        echo json_encode(['success'=>true,'data'=>MasterData::classificationGroups($archiveType)],JSON_UNESCAPED_UNICODE);
    }

    public function classificationItems(): void
    {
        Auth::requireOutgoingAccess();
        header('Content-Type: application/json; charset=utf-8');
        $group=strtoupper(trim($_GET['group']??''));
        if(!preg_match('/^[A-Z]{2,3}$/',$group)) {
            http_response_code(422); echo json_encode(['success'=>false,'message'=>'Kelompok klasifikasi tidak valid']); return;
        }
        echo json_encode(['success'=>true,'data'=>MasterData::classificationItems($group)],JSON_UNESCAPED_UNICODE);
    }

    public function classificationSearch(): void
    {
        Auth::requireOutgoingAccess();
        header('Content-Type: application/json; charset=utf-8');
        $q=trim($_GET['q']??'');
        $archive=$_GET['archive_type']??null;
        $group=strtoupper(trim($_GET['group']??'')) ?: null;
        echo json_encode([
            'success'=>true,
            'data'=>MasterData::searchClassifications($q,$archive ?: null,$group),
        ],JSON_UNESCAPED_UNICODE);
    }

    public function classifications(): void
    {
        Auth::requireOutgoingAccess();
        header('Content-Type: application/json; charset=utf-8');
        $scope=$_GET['scope']??'';
        $parent=$_GET['parent']??null;
        if(!preg_match('/^(YF|YS|TF|TS)$/',$scope)) {
            http_response_code(422); echo json_encode(['success'=>false,'message'=>'Scope tidak valid']); return;
        }
        echo json_encode(['success'=>true,'data'=>MasterData::classifications($scope,$parent?:null)],JSON_UNESCAPED_UNICODE);
    }
}
