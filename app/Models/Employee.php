<?php
namespace App\Models;

use App\Core\Database;

final class Employee
{
    public static function all(bool $activeOnly=false): array
    {
        $sql='SELECT e.*,wt.name AS work_team_name FROM employees e LEFT JOIN work_teams wt ON wt.id=e.work_team_id';
        if($activeOnly)$sql.=' WHERE e.is_active=1';
        $sql.=' ORDER BY e.is_active DESC,e.name';
        return Database::connection()->query($sql)->fetchAll();
    }
    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare('SELECT e.*,wt.name AS work_team_name FROM employees e LEFT JOIN work_teams wt ON wt.id=e.work_team_id WHERE e.id=? LIMIT 1');
        $stmt->execute([$id]);return $stmt->fetch()?:null;
    }
    public static function create(array $d,int $userId): int
    {
        $stmt=Database::connection()->prepare('INSERT INTO employees(name,nip,position,work_team_id,email,is_active,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,1,?,?,NOW(),NOW())');
        $stmt->execute([$d['name'],$d['nip']?:null,$d['position']?:null,$d['work_team_id']?:null,$d['email']?:null,$userId,$userId]);
        return (int)Database::connection()->lastInsertId();
    }
    public static function update(int $id,array $d,int $userId): void
    {
        $stmt=Database::connection()->prepare('UPDATE employees SET name=?,nip=?,position=?,work_team_id=?,email=?,updated_by=?,updated_at=NOW() WHERE id=?');
        $stmt->execute([$d['name'],$d['nip']?:null,$d['position']?:null,$d['work_team_id']?:null,$d['email']?:null,$userId,$id]);
    }
    public static function setActive(int $id,bool $active,int $userId): void
    {
        $stmt=Database::connection()->prepare('UPDATE employees SET is_active=?,updated_by=?,updated_at=NOW() WHERE id=?');
        $stmt->execute([$active?1:0,$userId,$id]);
    }
    public static function byNip(string $nip): ?array
    {
        if(trim($nip)==='')return null;
        $stmt=Database::connection()->prepare('SELECT * FROM employees WHERE nip=? LIMIT 1');$stmt->execute([trim($nip)]);return $stmt->fetch()?:null;
    }

    public static function recipientNamesByIds(array $ids): array
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($v)=>$v>0)));if(!$ids)return [];
        $ph=implode(',',array_fill(0,count($ids),'?'));$st=Database::connection()->prepare("SELECT id,name FROM employees WHERE id IN ($ph) AND is_active=1");$st->execute($ids);$rows=$st->fetchAll();$map=[];foreach($rows as $r)$map[(int)$r['id']]=$r['name'];$out=[];foreach($ids as $id)if(isset($map[$id]))$out[]=$map[$id];return $out;
    }

}