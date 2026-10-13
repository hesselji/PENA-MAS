<?php
namespace App\Models;

use App\Core\Database;

final class IncomingLetter
{
    public static function create(array $data): int
    {
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try{
            $stmt=$pdo->prepare(
                'INSERT INTO incoming_letters
                 (letter_number,origin,subject,recipient,letter_date,received_date,notes,workflow_status,created_by,recorded_at,created_at,updated_at)
                 VALUES (?,?,?,?,?,?,?,"MASUK",?,NOW(),NOW(),NOW())'
            );
            $stmt->execute([
                $data['letter_number'],$data['origin'],$data['subject'],$data['recipient'],$data['letter_date'],$data['received_date'],$data['notes']?:null,$data['created_by'],
            ]);
            $id=(int)$pdo->lastInsertId();
            $event=$pdo->prepare(
                'INSERT INTO incoming_letter_events
                 (incoming_letter_id,event_type,status_from,status_to,note,created_by,created_at)
                 VALUES (?,"CREATED",NULL,"MASUK",?, ?,NOW())'
            );
            $event->execute([$id,'Surat masuk dicatat pada PENA MAS.',$data['created_by']]);
            $pdo->commit();return $id;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public static function attach(int $id,array $file): void
    {
        $stmt=Database::connection()->prepare('UPDATE incoming_letters SET attachment_name=?,attachment_path=?,attachment_mime=?,attachment_size=?,updated_at=NOW() WHERE id=?');
        $stmt->execute([$file['name'],$file['path'],$file['mime'],$file['size'],$id]);
    }

    public static function search(array $filters = []): array
    {
        $where=[];$params=[];
        if (($filters['q'] ?? '') !== '') {
            $where[]='(il.letter_number LIKE :q1 OR il.origin LIKE :q2 OR il.subject LIKE :q3 OR il.recipient LIKE :q4)';
            $q='%'.trim($filters['q']).'%';$params['q1']=$q;$params['q2']=$q;$params['q3']=$q;$params['q4']=$q;
        }
        if (!empty($filters['year'])) {$where[]='YEAR(il.received_date)=:year';$params['year']=(int)$filters['year'];}
        if (!empty($filters['workflow_status']) && in_array($filters['workflow_status'],['MASUK','DISPOSISI','SELESAI'],true)) {
            $where[]='il.workflow_status=:ws';$params['ws']=$filters['workflow_status'];
        }
        $sql="SELECT il.*,u.name creator_name,wt.name assigned_team_name,au.name assigned_user_name,
                     (SELECT GROUP_CONCAT(DISTINCT wt2.name ORDER BY wt2.name SEPARATOR ', ') FROM incoming_letter_dispositions d2 JOIN work_teams wt2 ON wt2.id=d2.team_id WHERE d2.incoming_letter_id=il.id) AS disposition_teams,
                     (SELECT GROUP_CONCAT(DISTINCT u2.name ORDER BY u2.name SEPARATOR ', ') FROM incoming_letter_dispositions d3 JOIN users u2 ON u2.id=d3.assigned_user_id WHERE d3.incoming_letter_id=il.id) AS disposition_users
              FROM incoming_letters il
              JOIN users u ON u.id=il.created_by
              LEFT JOIN work_teams wt ON wt.id=il.assigned_team_id
              LEFT JOIN users au ON au.id=il.assigned_user_id";
        if ($where) $sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY il.recorded_at DESC,il.id DESC LIMIT 300';
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare(
            "SELECT il.*,u.name creator_name,u.email creator_email,wt.name assigned_team_name,au.name assigned_user_name,
                    su.name status_updated_by_name,
                    (SELECT GROUP_CONCAT(DISTINCT wt2.name ORDER BY wt2.name SEPARATOR ', ') FROM incoming_letter_dispositions d2 JOIN work_teams wt2 ON wt2.id=d2.team_id WHERE d2.incoming_letter_id=il.id) AS disposition_teams,
                    (SELECT GROUP_CONCAT(DISTINCT u2.name ORDER BY u2.name SEPARATOR ', ') FROM incoming_letter_dispositions d3 JOIN users u2 ON u2.id=d3.assigned_user_id WHERE d3.incoming_letter_id=il.id) AS disposition_users
             FROM incoming_letters il
             JOIN users u ON u.id=il.created_by
             LEFT JOIN work_teams wt ON wt.id=il.assigned_team_id
             LEFT JOIN users au ON au.id=il.assigned_user_id
             LEFT JOIN users su ON su.id=il.status_updated_by
             WHERE il.id=? LIMIT 1"
        );
        $stmt->execute([$id]);return $stmt->fetch() ?: null;
    }

    public static function events(int $id): array
    {
        $stmt=Database::connection()->prepare(
            'SELECT ie.*,u.name creator_name,wt.name assigned_team_name,au.name assigned_user_name
             FROM incoming_letter_events ie
             JOIN users u ON u.id=ie.created_by
             LEFT JOIN work_teams wt ON wt.id=ie.assigned_team_id
             LEFT JOIN users au ON au.id=ie.assigned_user_id
             WHERE ie.incoming_letter_id=? ORDER BY ie.id DESC'
        );
        $stmt->execute([$id]);return $stmt->fetchAll();
    }

    public static function dispositions(int $id): array
    {
        $stmt=Database::connection()->prepare(
            'SELECT d.*,wt.name AS team_name,u.name AS assigned_user_name
             FROM incoming_letter_dispositions d
             JOIN work_teams wt ON wt.id=d.team_id
             LEFT JOIN users u ON u.id=d.assigned_user_id
             WHERE d.incoming_letter_id=? ORDER BY wt.name,u.name'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public static function updateWorkflow(int $id, array $data, int $userId): void
    {
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $current=self::find($id);if(!$current)throw new \RuntimeException('Surat masuk tidak ditemukan.');
            $teams=array_values(array_unique(array_filter(array_map('intval',$data['assigned_teams']??[]),fn($v)=>$v>0)));
            $users=$data['assigned_users']??[];
            $primaryTeam=$teams[0]??0;
            $primaryUser=0;
            foreach($teams as $teamId){$uid=(int)($users[$teamId]??0);if($uid>0){$primaryUser=$uid;break;}}
            $stmt=$pdo->prepare(
                'UPDATE incoming_letters
                 SET workflow_status=?,assigned_team_id=?,assigned_user_id=?,follow_up_note=?,status_updated_at=NOW(),status_updated_by=?,updated_at=NOW()
                 WHERE id=?'
            );
            $stmt->execute([$data['workflow_status'],$primaryTeam?:null,$primaryUser?:null,$data['follow_up_note']?:null,$userId,$id]);

            $pdo->prepare('DELETE FROM incoming_letter_dispositions WHERE incoming_letter_id=?')->execute([$id]);
            if($teams){
                $ins=$pdo->prepare('INSERT INTO incoming_letter_dispositions(incoming_letter_id,team_id,assigned_user_id,note,created_by,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
                foreach($teams as $teamId){$uid=(int)($users[$teamId]??0);$ins->execute([$id,$teamId,$uid?:null,$data['follow_up_note']?:null,$userId]);}
            }

            $event=$pdo->prepare(
                'INSERT INTO incoming_letter_events
                 (incoming_letter_id,event_type,status_from,status_to,assigned_team_id,assigned_user_id,note,created_by,created_at)
                 VALUES (? ,"WORKFLOW_UPDATE",?,?,?,?,?,?,NOW())'
            );
            $event->execute([$id,$current['workflow_status'],$data['workflow_status'],$primaryTeam?:null,$primaryUser?:null,$data['follow_up_note']?:null,$userId]);
            $pdo->commit();
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

}
