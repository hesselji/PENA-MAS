<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\MasterData;

final class AdminMasterController
{
    private const ALLOWED_TOKENS = ['{PREFIX}','{SEQ}','{SEQ3}','{SEQ4}','{UNIT}','{KKA}','{YEAR}'];

    public function numbering(): void
    {
        Auth::requireAdmin();
        View::render('admin/numbering/index',[
            'rules'=>MasterData::numberingRules(),
            'types'=>MasterData::adminLetterTypes(),
        ]);
    }

    public function updateRule(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token']??null);

        $name=trim($_POST['name']??'');
        $unit=trim($_POST['unit_code']??'');
        $pattern=trim($_POST['pattern']??'');
        $active=isset($_POST['is_active']);

        if($name==='' || $unit==='' || $pattern==='') {
            $_SESSION['flash_error']='Nama pengaturan, kode unit, dan format nomor wajib diisi.';
            header('Location:/admin/numbering-rules');
            exit;
        }
        if(!str_contains($pattern,'{KKA}') || !str_contains($pattern,'{YEAR}') || !(str_contains($pattern,'{SEQ}') || str_contains($pattern,'{SEQ3}') || str_contains($pattern,'{SEQ4}'))) {
            $_SESSION['flash_error']='Format nomor belum lengkap. Pastikan bagian nomor urut, KKA, dan tahun tersedia.';
            header('Location:/admin/numbering-rules');
            exit;
        }
        preg_match_all('/\{[A-Z0-9_]+\}/',$pattern,$matches);
        foreach(array_unique($matches[0]??[]) as $token) {
            if(!in_array($token,self::ALLOWED_TOKENS,true)) {
                $_SESSION['flash_error']='Format nomor memuat bagian yang belum dikenali.';
                header('Location:/admin/numbering-rules');
                exit;
            }
        }

        MasterData::updateNumberingRule((int)$id,$name,$unit,$pattern,$active);
        AuditLog::write(Auth::id(),'UPDATE_NUMBERING_RULE','numbering_rule',(int)$id,[
            'unit_code'=>$unit,'pattern'=>$pattern,'active'=>$active,
        ]);
        $_SESSION['flash_success']='Aturan penomoran berhasil diperbarui.';
        header('Location:/admin/numbering-rules');
        exit;
    }

    public function updateLetterType(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token']??null);
        $ruleId=(int)($_POST['numbering_rule_id']??0);
        $active=isset($_POST['is_active']);
        if($ruleId<1) {
            $_SESSION['flash_error']='Aturan penomoran wajib dipilih.';
            header('Location:/admin/numbering-rules');
            exit;
        }
        MasterData::updateLetterTypeRule((int)$id,$ruleId,$active);
        AuditLog::write(Auth::id(),'UPDATE_LETTER_TYPE_RULE','letter_type',(int)$id,[
            'numbering_rule_id'=>$ruleId,'active'=>$active,
        ]);
        $_SESSION['flash_success']='Pengaturan jenis surat berhasil diperbarui.';
        header('Location:/admin/numbering-rules');
        exit;
    }

    public function classifications(): void
    {
        Auth::requireAdmin();
        $filters=[
            'archive_type'=>$_GET['archive_type']??'',
            'group'=>strtoupper(trim($_GET['group']??'')),
            'q'=>trim($_GET['q']??''),
        ];
        View::render('admin/classifications/index',[
            'filters'=>$filters,
            'rows'=>MasterData::classificationCatalog($filters),
            'groups'=>MasterData::classificationGroupStats(),
        ]);
    }
}
