<?php
namespace App\Models;
use App\Core\Database;
final class DocumentTemplate
{
    public static function all(bool $activeOnly=false): array{$sql='SELECT dt.*,lt.name AS letter_type_name FROM document_templates dt LEFT JOIN letter_types lt ON lt.id=dt.letter_type_id';if($activeOnly)$sql.=' WHERE dt.is_active=1';$sql.=' ORDER BY dt.is_active DESC,dt.name';return Database::connection()->query($sql)->fetchAll();}
    public static function activeForLetterType(int $letterTypeId): array{$st=Database::connection()->prepare('SELECT dt.*,lt.name AS letter_type_name FROM document_templates dt LEFT JOIN letter_types lt ON lt.id=dt.letter_type_id WHERE dt.is_active=1 AND (dt.letter_type_id IS NULL OR dt.letter_type_id=?) ORDER BY dt.letter_type_id IS NULL,dt.name');$st->execute([$letterTypeId]);return $st->fetchAll();}
    public static function find(int $id,bool $activeOnly=false): ?array{$sql='SELECT dt.*,lt.name AS letter_type_name FROM document_templates dt LEFT JOIN letter_types lt ON lt.id=dt.letter_type_id WHERE dt.id=?';if($activeOnly)$sql.=' AND dt.is_active=1';$sql.=' LIMIT 1';$st=Database::connection()->prepare($sql);$st->execute([$id]);return $st->fetch()?:null;}
    public static function create(array $d,int $userId): int{$st=Database::connection()->prepare('INSERT INTO document_templates(name,letter_type_id,description,content_html,source_file_name,source_file_path,source_format,is_active,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,?,?,NOW(),NOW())');$st->execute([$d['name'],$d['letter_type_id']?:null,$d['description']?:null,$d['content_html'],$d['source_file_name']?:null,$d['source_file_path']?:null,$d['source_format']?:'HTML',$userId,$userId]);return (int)Database::connection()->lastInsertId();}
    public static function update(int $id,array $d,int $userId): void{$current=self::find($id);$st=Database::connection()->prepare('UPDATE document_templates SET name=?,letter_type_id=?,description=?,content_html=?,source_file_name=?,source_file_path=?,source_format=?,updated_by=?,updated_at=NOW() WHERE id=?');$st->execute([$d['name'],$d['letter_type_id']?:null,$d['description']?:null,$d['content_html'],$d['source_file_name']?:($current['source_file_name']??null),$d['source_file_path']?:($current['source_file_path']??null),$d['source_format']?:($current['source_format']??'HTML'),$userId,$id]);}
    public static function updatePreview(int $id, string $html): void
    {
        Database::connection()->prepare("UPDATE document_templates SET content_html=?, source_format=CASE WHEN LOWER(COALESCE(source_file_name,'')) LIKE '%.docx' THEN 'DOCX_EXACT' ELSE source_format END, updated_at=NOW() WHERE id=?")->execute([$html,$id]);
    }

    public static function setActive(int $id,bool $active,int $userId): void{Database::connection()->prepare('UPDATE document_templates SET is_active=?,updated_by=?,updated_at=NOW() WHERE id=?')->execute([$active?1:0,$userId,$id]);}
}