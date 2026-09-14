<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\IncomingLetter;

final class IncomingLetterController
{
    public function index(): void
    {
        Auth::requireIncomingAccess();
        $filters=[
            'q'=>trim($_GET['q']??''),
            'year'=>$_GET['year']??date('Y'),
        ];
        View::render('incoming/index',[
            'letters'=>IncomingLetter::search($filters),
            'filters'=>$filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireIncomingAccess();
        View::render('incoming/create');
    }

    public function store(): void
    {
        Auth::requireIncomingAccess();
        Csrf::validate($_POST['_token']??null);

        $data=[
            'letter_number'=>trim($_POST['letter_number']??''),
            'origin'=>trim($_POST['origin']??''),
            'subject'=>trim($_POST['subject']??''),
            'recipient'=>trim($_POST['recipient']??''),
            'letter_date'=>$_POST['letter_date']??'',
            'received_date'=>$_POST['received_date']??date('Y-m-d'),
            'notes'=>trim($_POST['notes']??''),
            'created_by'=>(int)Auth::id(),
        ];

        $errors=[];
        foreach(['letter_number','origin','subject','recipient','letter_date','received_date'] as $field) {
            if ($data[$field] === '') $errors[$field]='Wajib diisi.';
        }
        foreach(['letter_date','received_date'] as $field) {
            $d=\DateTime::createFromFormat('Y-m-d',$data[$field]);
            if (!$d || $d->format('Y-m-d') !== $data[$field]) $errors[$field]='Tanggal tidak valid.';
        }
        if ($data['letter_date'] && $data['received_date'] && $data['letter_date'] > $data['received_date']) {
            $errors['letter_date']='Tanggal surat tidak boleh setelah tanggal diterima.';
        }

        if ($errors) {
            $_SESSION['errors']=$errors;
            $_SESSION['old']=$_POST;
            header('Location:/incoming/create');
            exit;
        }

        try {
            $id=IncomingLetter::create($data);
            AuditLog::write(Auth::id(),'CREATE_INCOMING_LETTER','incoming_letter',$id,[
                'letter_number'=>$data['letter_number'],
                'origin'=>$data['origin'],
            ]);
            unset($_SESSION['old']);
            $_SESSION['flash_success']='Surat masuk berhasil dicatat.';
            header('Location:/incoming/'.$id);
            exit;
        } catch(\Throwable $e) {
            $_SESSION['flash_error']='Gagal mencatat surat masuk.';
            $_SESSION['old']=$_POST;
            header('Location:/incoming/create');
            exit;
        }
    }

    public function show(string $id): void
    {
        Auth::requireIncomingAccess();
        $letter=IncomingLetter::find((int)$id);
        if (!$letter) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('incoming/show',['letter'=>$letter]);
    }
}
