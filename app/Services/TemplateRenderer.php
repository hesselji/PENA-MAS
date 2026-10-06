<?php
namespace App\Services;
final class TemplateRenderer
{
    public static function placeholders(): array{return [
        '{{NOMOR_SURAT}}'=>'Nomor surat','{{TANGGAL_SURAT}}'=>'Tanggal surat','{{TANGGAL_SURAT_LONG}}'=>'Tanggal surat lengkap','{{PERIHAL}}'=>'Perihal','{{TUJUAN}}'=>'Nama / tujuan','{{KKA}}'=>'Kode klasifikasi','{{JENIS_SURAT}}'=>'Jenis surat','{{TIM_KERJA}}'=>'Tim kerja','{{CATATAN}}'=>'Catatan','{{PEMBEBANAN}}'=>'Kode pembebanan','{{DASAR_PEMBEBANAN}}'=>'Dasar pembebanan','{{TEMPAT_TTD}}'=>'Tempat tanda tangan','{{KETERANGAN_TUGAS}}'=>'Keterangan tugas tambahan','{{TGL_PELAKSANAAN}}'=>'Tanggal pelaksanaan','{{TUJUAN_TUGAS}}'=>'Tujuan tugas / lokasi','{{NAMA_PENANDA_TANGAN}}'=>'Nama penanda tangan','{{JABATAN_PENANDA_TANGAN}}'=>'Jabatan penanda tangan','{{NIP_PENANDA_TANGAN}}'=>'NIP penanda tangan','{{QR_VERIFIKASI}}'=>'QR verifikasi prototype'];}
    public static function sanitize(string $html): string
    {
        $html=preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\\1>#is','',$html)??'';
        $html=strip_tags($html,'<div><section><article><p><br><strong><b><em><i><u><span><table><thead><tbody><tr><td><th><h1><h2><h3><h4><ul><ol><li><hr><img>');
        $html=preg_replace('/\\son\\w+\\s*=\\s*("[^"]*"|\'[^\']*\'|[^\\s>]+)/i','',$html)??$html;$html=preg_replace('/javascript\\s*:/i','',$html)??$html;
        $html=preg_replace_callback('/<([a-z0-9]+)([^>]*)>/i',static function(array $m): string{$tag=strtolower($m[1]);$attrs=$m[2]??'';$out='';if(preg_match('/\\bclass\\s*=\\s*["\']([a-z0-9 _-]+)["\']/i',$attrs,$cm))$out.=' class="'.htmlspecialchars($cm[1],ENT_QUOTES,'UTF-8').'"';if($tag==='img'&&preg_match('/\\bsrc\\s*=\\s*["\'](\/assets\/images\/[a-z0-9._-]+)["\']/i',$attrs,$sm))$out.=' src="'.htmlspecialchars($sm[1],ENT_QUOTES,'UTF-8').'"';foreach(['colspan','rowspan'] as $a)if(preg_match('/\\b'.$a.'\\s*=\\s*["\']?([0-9]{1,2})/i',$attrs,$am))$out.=' '.$a.'="'.(int)$am[1].'"';return '<'.$tag.$out.'>';},$html)??$html;return trim($html);
    }
    public static function render(string $templateHtml,array $letter,array $signer,array $extras=[]): string
    {
        $longDate='';if(!empty($letter['letter_date'])){$ts=strtotime($letter['letter_date']);$months=[1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];$longDate=date('d',$ts).' '.$months[(int)date('n',$ts)].' '.date('Y',$ts);}
        $vals=['{{NOMOR_SURAT}}'=>$letter['letter_number']??'','{{TANGGAL_SURAT}}'=>isset($letter['letter_date'])?\format_date_id($letter['letter_date']):'','{{TANGGAL_SURAT_LONG}}'=>$longDate,'{{PERIHAL}}'=>$letter['subject']??'','{{TUJUAN}}'=>$letter['recipient']??'','{{KKA}}'=>$letter['classification_code']??'','{{JENIS_SURAT}}'=>$letter['letter_type_name']??'','{{TIM_KERJA}}'=>$letter['work_team_name']??'','{{CATATAN}}'=>$letter['notes']?:'-','{{PEMBEBANAN}}'=>$extras['PEMBEBANAN']??'','{{DASAR_PEMBEBANAN}}'=>$extras['DASAR_PEMBEBANAN']??'','{{TEMPAT_TTD}}'=>$extras['TEMPAT_TTD']??'Palangka Raya','{{KETERANGAN_TUGAS}}'=>$extras['KETERANGAN_TUGAS']??'','{{TGL_PELAKSANAAN}}'=>$extras['TGL_PELAKSANAAN']??'','{{TUJUAN_TUGAS}}'=>$extras['TUJUAN_TUGAS']??'','{{NAMA_PENANDA_TANGAN}}'=>$signer['name']??'','{{JABATAN_PENANDA_TANGAN}}'=>$signer['position']??'','{{NIP_PENANDA_TANGAN}}'=>!empty($signer['nip'])?'NIP '.$signer['nip']:'','{{QR_VERIFIKASI}}'=>'__PENAMAS_QR_SLOT__'];
        foreach($vals as $k=>$v)if($k!=='{{QR_VERIFIKASI}}')$vals[$k]=htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$rendered=strtr(self::sanitize($templateHtml),$vals);return str_replace('__PENAMAS_QR_SLOT__','<div class="prototype-qr-slot"></div>',$rendered);
    }
}