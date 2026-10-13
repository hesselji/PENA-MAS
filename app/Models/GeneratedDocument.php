<?php
namespace App\Models;
use App\Core\Database;use App\Models\Signer;use App\Services\TemplateRenderer;
final class GeneratedDocument
{
    public static function createBatch(array $letters,array $template,array $signer,int $userId,?string $label=null,array $extras=[],array $signerMap=[]): array{$pdo=Database::connection();$pdo->beginTransaction();try{$pdo->prepare('INSERT INTO document_batches(label,created_by,document_count,created_at) VALUES (?,?,?,NOW())')->execute([$label?:null,$userId,count($letters)]);$batchId=(int)$pdo->lastInsertId();$ins=$pdo->prepare('INSERT INTO generated_documents(batch_id,outgoing_letter_id,template_id,signer_id,snapshot_html,custom_data_json,status,tte_status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,? ,"READY","NOT_CONNECTED",?,NOW(),NOW())');$ids=[];foreach($letters as $letter){$currentSigner=$signer;if(isset($signerMap[(int)$letter['id']])){$mapped=Signer::find((int)$signerMap[(int)$letter['id']],true);if($mapped)$currentSigner=$mapped;}$html=TemplateRenderer::render((string)$template['content_html'],$letter,$currentSigner,$extras);$custom=$extras;$custom['_snapshot']=['letter'=>['letter_number'=>$letter['letter_number']??'','letter_date'=>$letter['letter_date']??'','subject'=>$letter['subject']??'','recipient'=>$letter['recipient']??'','classification_code'=>$letter['classification_code']??'','letter_type_name'=>$letter['letter_type_name']??'','work_team_name'=>$letter['work_team_name']??'','notes'=>$letter['notes']??''],'signer'=>['name'=>$currentSigner['name']??'','position'=>$currentSigner['position']??'','nip'=>$currentSigner['nip']??'']];$ins->execute([$batchId,(int)$letter['id'],(int)$template['id'],(int)$currentSigner['id'],$html,json_encode($custom,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$userId]);$ids[]=(int)$pdo->lastInsertId();}$pdo->commit();return ['batch_id'=>$batchId,'document_ids'=>$ids];}catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}
    public static function find(int $id): ?array{$st=Database::connection()->prepare('SELECT gd.*,db.label AS batch_label,ol.letter_number,ol.sensitivity,ol.subject,ol.recipient,ol.status AS letter_status,dt.name AS template_name,s.name AS signer_name,s.position AS signer_position,s.nip AS signer_nip,s.prototype_tte_enabled,gd.signature_batch_id,u.name AS creator_name FROM generated_documents gd JOIN outgoing_letters ol ON ol.id=gd.outgoing_letter_id JOIN document_templates dt ON dt.id=gd.template_id JOIN signers s ON s.id=gd.signer_id JOIN users u ON u.id=gd.created_by LEFT JOIN document_batches db ON db.id=gd.batch_id WHERE gd.id=? LIMIT 1');$st->execute([$id]);return $st->fetch()?:null;}
    public static function findByToken(string $token): ?array{$st=Database::connection()->prepare('SELECT gd.*,ol.letter_number,ol.sensitivity,s.name AS signer_name,s.position AS signer_position FROM generated_documents gd JOIN outgoing_letters ol ON ol.id=gd.outgoing_letter_id JOIN signers s ON s.id=gd.signer_id WHERE gd.tte_reference=? AND gd.tte_status="SIGNED" LIMIT 1');$st->execute([$token]);return $st->fetch()?:null;}
    public static function batch(int $batchId): array{$st=Database::connection()->prepare('SELECT gd.*,ol.letter_number,ol.sensitivity,ol.subject,ol.recipient,ol.status AS letter_status,dt.name AS template_name,s.name AS signer_name,s.position AS signer_position,s.nip AS signer_nip,s.prototype_tte_enabled FROM generated_documents gd JOIN outgoing_letters ol ON ol.id=gd.outgoing_letter_id JOIN document_templates dt ON dt.id=gd.template_id JOIN signers s ON s.id=gd.signer_id WHERE gd.batch_id=? ORDER BY gd.id');$st->execute([$batchId]);return $st->fetchAll();}
    public static function batchInfo(int $id): ?array{$st=Database::connection()->prepare('SELECT db.*,u.name AS creator_name FROM document_batches db JOIN users u ON u.id=db.created_by WHERE db.id=? LIMIT 1');$st->execute([$id]);return $st->fetch()?:null;}
    public static function recent(int $limit=40): array{$limit=max(1,min(200,$limit));return Database::connection()->query('SELECT gd.id,gd.batch_id,gd.status,gd.tte_status,gd.created_at,gd.signed_at,ol.letter_number,ol.sensitivity,dt.name AS template_name,s.name AS signer_name,u.name AS creator_name FROM generated_documents gd JOIN outgoing_letters ol ON ol.id=gd.outgoing_letter_id JOIN document_templates dt ON dt.id=gd.template_id JOIN signers s ON s.id=gd.signer_id JOIN users u ON u.id=gd.created_by ORDER BY gd.id DESC LIMIT '.$limit)->fetchAll();}
    public static function markPrintedByBatch(int $id): void{Database::connection()->prepare('UPDATE generated_documents SET status="PRINTED",printed_at=COALESCE(printed_at,NOW()),updated_at=NOW() WHERE batch_id=?')->execute([$id]);}
    public static function markPrinted(int $id): void{Database::connection()->prepare('UPDATE generated_documents SET status="PRINTED",printed_at=COALESCE(printed_at,NOW()),updated_at=NOW() WHERE id=?')->execute([$id]);}
    public static function submit(int $id): void{Database::connection()->prepare('UPDATE generated_documents SET tte_status="PENDING",submitted_at=NOW(),rejected_at=NULL,rejection_note=NULL,updated_at=NOW() WHERE id=? AND tte_status IN ("NOT_CONNECTED","FAILED")')->execute([$id]);}
    public static function submitBatch(int $batchId): void{Database::connection()->prepare('UPDATE generated_documents SET tte_status="PENDING",submitted_at=NOW(),rejected_at=NULL,rejection_note=NULL,updated_at=NOW() WHERE batch_id=? AND tte_status IN ("NOT_CONNECTED","FAILED")')->execute([$batchId]);}
    public static function sign(int $id): string{$doc=self::find($id);if(!$doc)throw new \RuntimeException('Dokumen tidak ditemukan.');$token=bin2hex(random_bytes(18));$hash=hash('sha256',(string)$doc['snapshot_html'].'|'.$token.'|'.$doc['signer_id'].'|'.microtime(true));Database::connection()->prepare('UPDATE generated_documents SET tte_status="SIGNED",tte_reference=?,signature_hash=?,signed_at=NOW(),rejected_at=NULL,rejection_note=NULL,updated_at=NOW() WHERE id=? AND tte_status="PENDING"')->execute([$token,$hash,$id]);return $token;}
    public static function signBatch(int $batchId): array{$docs=self::batch($batchId);$ids=[];foreach($docs as $d){if($d['tte_status']!=='PENDING')continue;self::sign((int)$d['id']);$ids[]=(int)$d['id'];}return $ids;}
    public static function createOrRefreshSignatureBatch(array $documentIds,int $userId,?string $label=null): ?int
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$documentIds),fn($v)=>$v>0)));if(!$ids)return null;
        $ph=implode(',',array_fill(0,count($ids),'?'));
        $st=Database::connection()->prepare('SELECT DISTINCT signature_batch_id FROM generated_documents WHERE id IN ('.$ph.') AND tte_status="SIGNED"');$st->execute($ids);
        $existing=array_values(array_filter(array_map('intval',$st->fetchAll(\PDO::FETCH_COLUMN)),fn($v)=>$v>0));
        if(count($existing)===1){
            $batchId=$existing[0];
            Database::connection()->prepare('UPDATE generated_documents SET signature_batch_id=?,updated_at=NOW() WHERE id IN ('.$ph.') AND tte_status="SIGNED"')->execute(array_merge([$batchId],$ids));
            Database::connection()->prepare('UPDATE signed_document_batches SET document_count=(SELECT COUNT(*) FROM generated_documents WHERE signature_batch_id=? AND tte_status="SIGNED") WHERE id=?')->execute([$batchId,$batchId]);
            return $batchId;
        }
        return self::createSignatureBatch($ids,$userId,$label);
    }

    public static function createSignatureBatch(array $documentIds,int $userId,?string $label=null): ?int
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$documentIds),fn($v)=>$v>0)));if(!$ids)return null;
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $ph=implode(',',array_fill(0,count($ids),'?'));
            $check=$pdo->prepare('SELECT id FROM generated_documents WHERE id IN ('.$ph.') AND tte_status="SIGNED" ORDER BY id');$check->execute($ids);$signed=array_map('intval',$check->fetchAll(\PDO::FETCH_COLUMN));
            if(!$signed){$pdo->rollBack();return null;}
            $pdo->prepare('INSERT INTO signed_document_batches(label,created_by,document_count,created_at) VALUES (?,?,?,NOW())')->execute([$label?:'Batch Surat Ditandatangani - '.date('d-m-Y H:i'),$userId,count($signed)]);
            $batchId=(int)$pdo->lastInsertId();
            $ph=implode(',',array_fill(0,count($signed),'?'));$pdo->prepare('UPDATE generated_documents SET signature_batch_id=?,updated_at=NOW() WHERE id IN ('.$ph.')')->execute(array_merge([$batchId],$signed));
            $pdo->commit();return $batchId;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public static function signedBatchInfo(int $id): ?array{
        $st=Database::connection()->prepare('SELECT sb.*,u.name AS creator_name FROM signed_document_batches sb JOIN users u ON u.id=sb.created_by WHERE sb.id=? LIMIT 1');$st->execute([$id]);return $st->fetch()?:null;
    }
    public static function signedBatch(int $id): array{
        $st=Database::connection()->prepare('SELECT gd.*,ol.letter_number,ol.sensitivity,ol.subject,ol.recipient,dt.name AS template_name,s.name AS signer_name,s.position AS signer_position,s.nip AS signer_nip FROM generated_documents gd JOIN outgoing_letters ol ON ol.id=gd.outgoing_letter_id JOIN document_templates dt ON dt.id=gd.template_id JOIN signers s ON s.id=gd.signer_id WHERE gd.signature_batch_id=? AND gd.tte_status="SIGNED" ORDER BY gd.id');$st->execute([$id]);return $st->fetchAll();
    }
    public static function markPrintedBySignatureBatch(int $id): void{Database::connection()->prepare('UPDATE generated_documents gd JOIN signed_document_batches sb ON sb.id=gd.signature_batch_id SET gd.status="PRINTED",gd.printed_at=COALESCE(gd.printed_at,NOW()),gd.updated_at=NOW() WHERE sb.id=?')->execute([$id]);}

    public static function reject(int $id,string $reason): void{Database::connection()->prepare('UPDATE generated_documents SET tte_status="FAILED",rejected_at=NOW(),rejection_note=?,updated_at=NOW() WHERE id=? AND tte_status="PENDING"')->execute([$reason?:'Ditolak pada prototype.',$id]);}
    public static function rejectBatch(int $batchId,string $reason): int{$st=Database::connection()->prepare('UPDATE generated_documents SET tte_status="FAILED",rejected_at=NOW(),rejection_note=?,updated_at=NOW() WHERE batch_id=? AND tte_status="PENDING"');$st->execute([$reason?:'Ditolak pada prototype.',$batchId]);return $st->rowCount();}
}