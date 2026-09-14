<?php
namespace App\Models;

use App\Core\Database;

final class IncomingLetter
{
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO incoming_letters
             (letter_number,origin,subject,recipient,letter_date,received_date,notes,created_by,recorded_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,NOW(),NOW(),NOW())'
        );
        $stmt->execute([
            $data['letter_number'],
            $data['origin'],
            $data['subject'],
            $data['recipient'],
            $data['letter_date'],
            $data['received_date'],
            $data['notes'] ?: null,
            $data['created_by'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function search(array $filters = []): array
    {
        $where=[];$params=[];
        if (($filters['q'] ?? '') !== '') {
            $where[]='(il.letter_number LIKE :q1 OR il.origin LIKE :q2 OR il.subject LIKE :q3 OR il.recipient LIKE :q4)';
            $q='%'.trim($filters['q']).'%';
            $params['q1']=$q;$params['q2']=$q;$params['q3']=$q;$params['q4']=$q;
        }
        if (!empty($filters['year'])) {
            $where[]='YEAR(il.received_date)=:year';
            $params['year']=(int)$filters['year'];
        }
        $sql='SELECT il.*,u.name creator_name
              FROM incoming_letters il
              JOIN users u ON u.id=il.created_by';
        if ($where) $sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY il.recorded_at DESC,il.id DESC LIMIT 300';
        $stmt=Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare(
            'SELECT il.*,u.name creator_name,u.email creator_email
             FROM incoming_letters il
             JOIN users u ON u.id=il.created_by
             WHERE il.id=? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
