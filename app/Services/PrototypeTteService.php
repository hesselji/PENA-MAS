<?php
namespace App\Services;

use App\Core\Env;

final class PrototypeTteService
{
    public static function verificationUrl(string $token): string
    {
        return rtrim(Env::get('APP_URL',''),'\/').'/verify/'.rawurlencode($token);
    }
    public static function qrUrl(string $token): string
    {
        return 'https://quickchart.io/qr?size=180&margin=1&text='.rawurlencode(self::verificationUrl($token));
    }
    public static function decorate(string $html,array $document): string
    {
        $signed=($document['tte_status']??'')==='SIGNED'&&!empty($document['tte_reference']);
        $block=$signed?self::signedBlock($document):'<div class="prototype-qr-slot pending"><strong>Menunggu tanda tangan</strong><small>Kode verifikasi akan muncul setelah proses tanda tangan prototype selesai.</small></div>';
        if(str_contains($html,'<div class="prototype-qr-slot"></div>'))return str_replace('<div class="prototype-qr-slot"></div>',$block,$html);
        return $html.($signed?$block:'');
    }
    private static function signedBlock(array $d): string
    {
        $token=(string)$d['tte_reference'];$hash=(string)($d['signature_hash']??'');$date=isset($d['signed_at'])&&$d['signed_at']?\format_date_id($d['signed_at'],true):'-';
        return '<div class="prototype-verification"><img src="'.htmlspecialchars(self::qrUrl($token),ENT_QUOTES,'UTF-8').'" alt="QR verifikasi prototype"><div><strong>Ditandatangani - Prototype Internal</strong><span>'.htmlspecialchars($d['signer_name']??'',ENT_QUOTES,'UTF-8').'</span><small>'.$date.'</small><small>Kode: '.htmlspecialchars(substr($token,0,12),ENT_QUOTES,'UTF-8').'...</small><small>Hash: '.htmlspecialchars(substr($hash,0,18),ENT_QUOTES,'UTF-8').'...</small><a href="'.htmlspecialchars(self::verificationUrl($token),ENT_QUOTES,'UTF-8').'">Verifikasi dokumen</a></div></div>';
    }
}