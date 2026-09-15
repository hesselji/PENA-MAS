<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use PDO;

final class Report
{
    public static function outgoing(array $filters, ?int $limit = 300): array
    {
        [$where, $params] = self::outgoingWhere($filters);

        $sql = 'SELECT
                    ol.id,
                    ol.agenda_number,
                    ol.letter_number,
                    ol.letter_date,
                    ol.sensitivity,
                    ol.status,
                    ol.subject,
                    ol.recipient,
                    ol.classification_code,
                    ol.archive_type,
                    ol.system_type,
                    ol.uses_budget,
                    ol.created_at,
                    wt.name AS work_team_name,
                    lt.name AS letter_type_name,
                    u.name AS requester_name
                FROM outgoing_letters ol
                JOIN work_teams wt ON wt.id=ol.work_team_id
                JOIN letter_types lt ON lt.id=ol.letter_type_id
                JOIN users u ON u.id=ol.requested_by';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY ol.letter_date DESC, ol.id DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, (int)$limit);
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function outgoingSummary(array $filters): array
    {
        [$where, $params] = self::outgoingWhere($filters);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(ol.status='ACTIVE') AS active_count,
                    SUM(ol.status='CANCELLED') AS cancelled_count,
                    SUM(ol.sensitivity IN ('RAHASIA','SANGAT_RAHASIA')) AS confidential_count
                FROM outgoing_letters ol
                JOIN work_teams wt ON wt.id=ol.work_team_id
                JOIN letter_types lt ON lt.id=ol.letter_type_id
                JOIN users u ON u.id=ol.requested_by";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active_count'] ?? 0),
            'cancelled' => (int)($row['cancelled_count'] ?? 0),
            'confidential' => (int)($row['confidential_count'] ?? 0),
        ];
    }

    public static function incoming(array $filters, ?int $limit = 300): array
    {
        [$where, $params] = self::incomingWhere($filters);

        $sql = 'SELECT
                    il.id,
                    il.letter_number,
                    il.origin,
                    il.subject,
                    il.recipient,
                    il.letter_date,
                    il.received_date,
                    il.recorded_at,
                    il.created_at,
                    u.name AS creator_name
                FROM incoming_letters il
                JOIN users u ON u.id=il.created_by';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY il.received_date DESC, il.id DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, (int)$limit);
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function incomingSummary(array $filters): array
    {
        [$where, $params] = self::incomingWhere($filters);

        $sql = 'SELECT
                    COUNT(*) AS total,
                    COUNT(DISTINCT il.origin) AS origin_count,
                    COUNT(DISTINCT il.created_by) AS recorder_count,
                    MAX(il.received_date) AS latest_received
                FROM incoming_letters il
                JOIN users u ON u.id=il.created_by';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'origins' => (int)($row['origin_count'] ?? 0),
            'recorders' => (int)($row['recorder_count'] ?? 0),
            'latest_received' => $row['latest_received'] ?? null,
        ];
    }

    private static function outgoingWhere(array $filters): array
    {
        $where = [];
        $params = [];

        // USER hanya boleh memperoleh surat Biasa. ADMIN dapat melihat seluruh sifat surat.
        if (!Auth::isAdmin()) {
            $where[] = "ol.sensitivity='BIASA'";
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'ol.letter_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'ol.letter_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(ol.letter_number LIKE :q1 OR ol.subject LIKE :q2 OR ol.recipient LIKE :q3 OR u.name LIKE :q4)';
            $q = '%' . trim((string)$filters['q']) . '%';
            $params['q1'] = $q;
            $params['q2'] = $q;
            $params['q3'] = $q;
            $params['q4'] = $q;
        }

        if (!empty($filters['team'])) {
            $where[] = 'ol.work_team_id = :team';
            $params['team'] = (int)$filters['team'];
        }

        if (!empty($filters['letter_type'])) {
            $where[] = 'ol.letter_type_id = :letter_type';
            $params['letter_type'] = (int)$filters['letter_type'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['ACTIVE','CANCELLED'], true)) {
            $where[] = 'ol.status = :status';
            $params['status'] = $filters['status'];
        }

        if (
            Auth::isAdmin()
            && !empty($filters['sensitivity'])
            && in_array($filters['sensitivity'], ['BIASA','RAHASIA','SANGAT_RAHASIA'], true)
        ) {
            $where[] = 'ol.sensitivity = :sensitivity';
            $params['sensitivity'] = $filters['sensitivity'];
        }

        return [$where, $params];
    }

    private static function incomingWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'il.received_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'il.received_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(il.letter_number LIKE :q1 OR il.origin LIKE :q2 OR il.subject LIKE :q3 OR il.recipient LIKE :q4 OR u.name LIKE :q5)';
            $q = '%' . trim((string)$filters['q']) . '%';
            $params['q1'] = $q;
            $params['q2'] = $q;
            $params['q3'] = $q;
            $params['q4'] = $q;
            $params['q5'] = $q;
        }

        return [$where, $params];
    }
}
