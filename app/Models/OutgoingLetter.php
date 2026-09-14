<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class OutgoingLetter
{
    public static function dashboardStats(): array
    {
        $pdo = Database::connection();

        // ADMIN melihat statistik global. USER hanya statistik surat yang dia buat sendiri.
        if (Auth::isAdmin()) {
            $where = '1=1';
        } else {
            $where = "ol.requested_by=" . (int) Auth::id() . " AND ol.sensitivity='BIASA'";
        }

        $summary = $pdo->query(
            "SELECT COUNT(*) total,
                    SUM(ol.status='CANCELLED') cancelled,
                    SUM(YEAR(ol.created_at)=YEAR(CURDATE()) AND MONTH(ol.created_at)=MONTH(CURDATE())) this_month,
                    SUM(ol.sensitivity='BIASA') ordinary
             FROM outgoing_letters ol WHERE {$where}"
        )->fetch();

        $byTeam = $pdo->query(
            "SELECT wt.name,COUNT(*) total
             FROM outgoing_letters ol
             JOIN work_teams wt ON wt.id=ol.work_team_id
             WHERE {$where}
             GROUP BY wt.id,wt.name ORDER BY total DESC LIMIT 8"
        )->fetchAll();

        $bySensitivity = $pdo->query(
            "SELECT ol.sensitivity,COUNT(*) total
             FROM outgoing_letters ol WHERE {$where}
             GROUP BY ol.sensitivity ORDER BY total DESC"
        )->fetchAll();

        $latest = $pdo->query(
            "SELECT ol.*,wt.name work_team_name,u.name requester_name
             FROM outgoing_letters ol
             JOIN work_teams wt ON wt.id=ol.work_team_id
             JOIN users u ON u.id=ol.requested_by
             WHERE {$where}
             ORDER BY ol.id DESC LIMIT 6"
        )->fetchAll();

        return compact('summary','byTeam','bySensitivity','latest');
    }

    public static function search(array $filters): array
    {
        $where = [];
        $params = [];

        // USER boleh melihat seluruh surat BIASA dari semua pembuat.
        // ADMIN boleh melihat seluruh record termasuk Rahasia/Sangat Rahasia.
        if (!Auth::isAdmin()) {
            $where[] = "ol.sensitivity='BIASA'";
        }

        if (($filters['q'] ?? '') !== '') {
            $where[]='(ol.letter_number LIKE :q1 OR ol.recipient LIKE :q2 OR ol.subject LIKE :q3 OR u.name LIKE :q4)';
            $v='%'.trim($filters['q']).'%';
            $params['q1']=$v;$params['q2']=$v;$params['q3']=$v;$params['q4']=$v;
        }
        if (!empty($filters['team'])) { $where[]='ol.work_team_id=:team'; $params['team']=(int)$filters['team']; }
        if (!empty($filters['sensitivity'])) {
            // USER tidak boleh memaksa filter ke sifat rahasia via URL.
            if (Auth::isAdmin() || $filters['sensitivity'] === 'BIASA') {
                $where[]='ol.sensitivity=:sens';
                $params['sens']=$filters['sensitivity'];
            }
        }
        if (!empty($filters['status'])) { $where[]='ol.status=:status'; $params['status']=$filters['status']; }
        if (!empty($filters['year'])) { $where[]='ol.year=:year'; $params['year']=(int)$filters['year']; }

        $sql='SELECT ol.*,wt.name work_team_name,u.name requester_name,lt.name letter_type_name
              FROM outgoing_letters ol
              JOIN work_teams wt ON wt.id=ol.work_team_id
              JOIN users u ON u.id=ol.requested_by
              JOIN letter_types lt ON lt.id=ol.letter_type_id';
        if ($where) $sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY ol.id DESC LIMIT 300';
        $stmt=Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare(
            'SELECT ol.*,wt.name work_team_name,u.name requester_name,lt.name letter_type_name,
                    nr.code rule_code,nr.unit_code,cby.name cancelled_by_name,
                    cg.name classification_group_name,ci.name classification_item_name
             FROM outgoing_letters ol
             JOIN work_teams wt ON wt.id=ol.work_team_id
             JOIN users u ON u.id=ol.requested_by
             JOIN letter_types lt ON lt.id=ol.letter_type_id
             JOIN numbering_rules nr ON nr.id=lt.numbering_rule_id
             LEFT JOIN users cby ON cby.id=ol.cancelled_by
             LEFT JOIN classification_groups cg ON cg.code=ol.classification_parent
             LEFT JOIN classification_items ci ON ci.group_code=ol.classification_parent AND ci.code=ol.classification_child
             WHERE ol.id=?'
        );
        $stmt->execute([$id]);
        $row=$stmt->fetch();
        if (!$row) return null;

        if (!Auth::isAdmin() && $row['sensitivity'] !== 'BIASA') return null;
        return $row;
    }

    public static function findRaw(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ol.*,wt.name work_team_name,u.name requester_name,lt.name letter_type_name,
                    nr.code rule_code,nr.unit_code,cby.name cancelled_by_name,
                    cg.name classification_group_name,ci.name classification_item_name
             FROM outgoing_letters ol
             JOIN work_teams wt ON wt.id=ol.work_team_id
             JOIN users u ON u.id=ol.requested_by
             JOIN letter_types lt ON lt.id=ol.letter_type_id
             JOIN numbering_rules nr ON nr.id=lt.numbering_rule_id
             LEFT JOIN users cby ON cby.id=ol.cancelled_by
             LEFT JOIN classification_groups cg ON cg.code=ol.classification_parent
             LEFT JOIN classification_items ci ON ci.group_code=ol.classification_parent AND ci.code=ol.classification_child
             WHERE ol.id=?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function isConfidential(array $letter): bool
    {
        return in_array($letter['sensitivity'] ?? '', ['RAHASIA','SANGAT_RAHASIA'], true);
    }

    public static function cancel(int $id, int $userId, string $reason): void
    {
        $stmt=Database::connection()->prepare(
            "UPDATE outgoing_letters
             SET status='CANCELLED', cancellation_reason=?, cancelled_by=?, cancelled_at=NOW(), updated_at=NOW()
             WHERE id=? AND status='ACTIVE'"
        );
        $stmt->execute([$reason,$userId,$id]);
    }
}
