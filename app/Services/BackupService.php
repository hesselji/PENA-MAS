<?php
namespace App\Services;

use App\Core\Database;

final class BackupService
{
    private const TABLES=[
        'work_teams','users','numbering_rules','letter_types','classification_groups','classification_items','number_sequences',
        'employees','signers','outgoing_letters','incoming_letters','incoming_letter_events','document_templates','document_batches',
        'generated_documents','signed_document_batches','incoming_letter_dispositions','notifications','audit_logs'
    ];
    public static function export(): array
    {
        $pdo=Database::connection();$tables=[];
        foreach(self::TABLES as $table){
            try{$tables[$table]=$pdo->query('SELECT * FROM `'.$table.'`')->fetchAll();}catch(\Throwable $e){$tables[$table]=[];}
        }
        return ['application'=>'PENA MAS','format'=>'penamas-json-backup','version'=>3,'created_at'=>date(DATE_ATOM),'tables'=>$tables];
    }
    public static function restore(array $payload): void
    {
        if(($payload['application']??'')!=='PENA MAS'||($payload['format']??'')!=='penamas-json-backup'||!isset($payload['tables'])||!is_array($payload['tables'])) throw new \RuntimeException('Berkas backup tidak dikenali.');
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach(array_reverse(self::TABLES) as $table){try{$pdo->exec('DELETE FROM `'.$table.'`');}catch(\Throwable $e){}}
            foreach(self::TABLES as $table){
                $rows=$payload['tables'][$table]??[];if(!is_array($rows)||!$rows)continue;
                foreach($rows as $row){if(!is_array($row)||!$row)continue;$cols=array_keys($row);$q='INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';$pdo->prepare($q)->execute(array_values($row));}
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');$pdo->commit();
        }catch(\Throwable $e){try{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}catch(\Throwable $x){}if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}