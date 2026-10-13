<?php
namespace App\Models;
use App\Core\Database;
final class Signer
{
    public static function all(bool $activeOnly=false): array{$sql='SELECT s.*,wt.name AS work_team_name,e.name AS employee_name FROM signers s LEFT JOIN work_teams wt ON wt.id=s.work_team_id LEFT JOIN employees e ON e.id=s.employee_id';if($activeOnly)$sql.=' WHERE s.is_active=1';$sql.=' ORDER BY s.is_active DESC,s.name';return Database::connection()->query($sql)->fetchAll();}
    public static function find(int $id,bool $activeOnly=false): ?array{$sql='SELECT s.*,wt.name AS work_team_name,e.name AS employee_name FROM signers s LEFT JOIN work_teams wt ON wt.id=s.work_team_id LEFT JOIN employees e ON e.id=s.employee_id WHERE s.id=?';if($activeOnly)$sql.=' AND s.is_active=1';$sql.=' LIMIT 1';$st=Database::connection()->prepare($sql);$st->execute([$id]);return $st->fetch()?:null;}
    public static function findByEmployeeId(int $employeeId): ?array{
        $st=Database::connection()->prepare('SELECT * FROM signers WHERE employee_id=? LIMIT 1');$st->execute([$employeeId]);return $st->fetch()?:null;
    }
    public static function create(array $d,int $userId): int{$hash=trim((string)($d['passkey']??''))!==''?password_hash((string)$d['passkey'],PASSWORD_BCRYPT):null;$st=Database::connection()->prepare('INSERT INTO signers(name,position,nip,work_team_id,employee_id,prototype_passkey_hash,prototype_tte_enabled,is_active,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,?,?,NOW(),NOW())');$st->execute([$d['name'],$d['position'],$d['nip']?:null,$d['work_team_id']?:null,$d['employee_id']?:null,$hash,$hash?1:0,$userId,$userId]);return (int)Database::connection()->lastInsertId();}
    public static function createAutoForEmployee(array $employee,int $userId): array{
        $existing=self::findByEmployeeId((int)$employee['id']);
        if($existing)return ['id'=>(int)$existing['id'],'passkey'=>null,'created'=>false];
        $passkey=strtoupper(bin2hex(random_bytes(4)));
        $id=self::create(['name'=>$employee['name'],'position'=>$employee['position']?:'Penanda Tangan','nip'=>$employee['nip']?:'','work_team_id'=>(int)($employee['work_team_id']??0),'employee_id'=>(int)$employee['id'],'passkey'=>$passkey],$userId);
        return ['id'=>$id,'passkey'=>$passkey,'created'=>true];
    }
    public static function update(int $id,array $d,int $userId): void{$sql='UPDATE signers SET name=?,position=?,nip=?,work_team_id=?,employee_id=?,updated_by=?,updated_at=NOW()';$params=[$d['name'],$d['position'],$d['nip']?:null,$d['work_team_id']?:null,$d['employee_id']?:null,$userId];if(trim((string)($d['passkey']??''))!==''){$sql.=',prototype_passkey_hash=?,prototype_tte_enabled=1';$params[]=password_hash((string)$d['passkey'],PASSWORD_BCRYPT);}$sql.=' WHERE id=?';$params[]=$id;Database::connection()->prepare($sql)->execute($params);}
    public static function setActive(int $id,bool $active,int $userId): void{Database::connection()->prepare('UPDATE signers SET is_active=?,updated_by=?,updated_at=NOW() WHERE id=?')->execute([$active?1:0,$userId,$id]);}
    public static function verifyPrototypePasskey(array $signer,string $passkey): bool{return !empty($signer['prototype_tte_enabled'])&&!empty($signer['prototype_passkey_hash'])&&password_verify($passkey,(string)$signer['prototype_passkey_hash']);}
}