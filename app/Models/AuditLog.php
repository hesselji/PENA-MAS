<?php
namespace App\Models;

use App\Core\Database;

final class AuditLog
{
    public static function write(?int $userId, string $action, string $entityType, ?int $entityId, array $meta = []): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,metadata_json,ip_address,created_at) VALUES (?,?,?,?,?,?,NOW())');
        $stmt->execute([$userId,$action,$entityType,$entityId,json_encode($meta, JSON_UNESCAPED_UNICODE),$_SERVER['REMOTE_ADDR'] ?? null]);
    }

    public static function search(array $filters = [], int $limit = 250): array
    {
        $where=[];$params=[];
        if(!empty($filters['user_id'])){$where[]='al.user_id=:uid';$params['uid']=(int)$filters['user_id'];}
        if(!empty($filters['date_from'])){$where[]='DATE(al.created_at)>=:df';$params['df']=$filters['date_from'];}
        if(!empty($filters['date_to'])){$where[]='DATE(al.created_at)<=:dt';$params['dt']=$filters['date_to'];}
        if(!empty($filters['q'])){
            $where[]='(u.name LIKE :q1 OR al.action LIKE :q2 OR al.entity_type LIKE :q3)';
            $v='%'.trim($filters['q']).'%';$params['q1']=$v;$params['q2']=$v;$params['q3']=$v;
        }
        $sql='SELECT al.*,u.name AS user_name FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id';
        if($where)$sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY al.id DESC LIMIT '.max(1,min(500,$limit));
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll();
        foreach($rows as &$row){$row['description']=self::describe($row);$row['category']=self::category($row['action']);}
        unset($row);return $rows;
    }

    public static function describe(array $row): string
    {
        $name=\display_user_name($row['user_name']??'Sistem');
        $map=[
            'GENERATE_NUMBER'=>"{$name} membuat nomor surat",
            'GENERATE_BATCH'=>"{$name} membuat beberapa nomor surat",
            'CANCEL_NUMBER'=>"{$name} membatalkan nomor surat",
            'VIEW_CONFIDENTIAL_LETTER'=>"{$name} membuka surat terlindungi",
            'CONFIDENTIAL_ACCESS_FAILED'=>"Percobaan akses surat terlindungi ditolak",
            'CREATE_INCOMING_LETTER'=>"{$name} mencatat surat masuk",
            'UPDATE_INCOMING_WORKFLOW'=>"{$name} memperbarui tindak lanjut surat masuk",
            'LOGIN'=>"{$name} masuk ke PENA MAS",
            'LOGOUT'=>"{$name} keluar dari PENA MAS",
            'CHANGE_PASSWORD'=>"{$name} mengganti password",
            'CREATE_USER'=>"{$name} menambahkan pengguna",
            'UPDATE_USER'=>"{$name} memperbarui pengguna",
            'RESET_USER_PASSWORD'=>"{$name} mengatur ulang password pengguna",
            'ACTIVATE_USER'=>"{$name} mengaktifkan akun",
            'FREEZE_USER'=>"{$name} membekukan akun",
            'DELETE_USER'=>"{$name} menghapus akun",
            'EXPORT_REPORT_CSV'=>"{$name} mengunduh laporan CSV",
            'OPEN_PRINT_REPORT'=>"{$name} membuka laporan cetak",
            'CREATE_SIGNER'=>"{$name} menambahkan penanda tangan",
            'UPDATE_SIGNER'=>"{$name} memperbarui penanda tangan",
            'ACTIVATE_SIGNER'=>"{$name} mengaktifkan penanda tangan",
            'DEACTIVATE_SIGNER'=>"{$name} menonaktifkan penanda tangan",
            'CREATE_DOCUMENT_TEMPLATE'=>"{$name} membuat template dokumen",
            'UPDATE_DOCUMENT_TEMPLATE'=>"{$name} memperbarui template dokumen",
            'ACTIVATE_DOCUMENT_TEMPLATE'=>"{$name} mengaktifkan template dokumen",
            'DEACTIVATE_DOCUMENT_TEMPLATE'=>"{$name} menonaktifkan template dokumen",
            'GENERATE_DOCUMENT'=>"{$name} membuat dokumen dari agenda surat",
            'GENERATE_DOCUMENT_BATCH'=>"{$name} membuat batch dokumen",
            'PRINT_DOCUMENT'=>"{$name} membuka dokumen untuk dicetak",
            'PRINT_DOCUMENT_BATCH'=>"{$name} membuka batch dokumen untuk dicetak",
            'CREATE_EMPLOYEE'=>"{$name} menambahkan data pegawai",
            'UPDATE_EMPLOYEE'=>"{$name} memperbarui data pegawai",
            'SUBMIT_DOCUMENT_SIGNATURE'=>"{$name} mengajukan dokumen untuk tanda tangan",
            'SUBMIT_DOCUMENT_BATCH_SIGNATURE'=>"{$name} mengajukan batch dokumen untuk tanda tangan",
            'PROTOTYPE_SIGN_DOCUMENT'=>"{$name} menandatangani dokumen pada mode prototype",
            'PROTOTYPE_SIGN_DOCUMENT_BATCH'=>"{$name} menandatangani batch dokumen pada mode prototype",
            'REJECT_DOCUMENT_SIGNATURE'=>"{$name} menolak pengajuan tanda tangan dokumen",
            'CREATE_BACKUP'=>"{$name} mengunduh backup PENA MAS",
        ];
        return $map[$row['action']] ?? ($name.' melakukan aktivitas pada PENA MAS');
    }

    private static function category(string $action): string
    {
        if(str_contains($action,'USER')||str_contains($action,'EMPLOYEE'))return 'Akun & Pegawai';
        if(str_contains($action,'BACKUP'))return 'Sistem';
        if(str_contains($action,'DOCUMENT')||str_contains($action,'SIGNER')||str_contains($action,'TEMPLATE'))return 'Dokumen';
        if(str_contains($action,'INCOMING'))return 'Surat Masuk';
        if(str_contains($action,'REPORT'))return 'Laporan';
        if(str_contains($action,'CONFIDENTIAL'))return 'Keamanan';
        return 'Surat Keluar';
    }
}
