<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class MasterData
{
    public static function workTeams(): array
    {
        return Database::connection()->query('SELECT id,name FROM work_teams WHERE is_active=1 ORDER BY name')->fetchAll();
    }

    public static function letterTypes(): array
    {
        return Database::connection()->query(
            'SELECT lt.id,lt.code,lt.name,nr.id AS rule_id,nr.code AS rule_code,nr.name AS rule_name,nr.unit_code,nr.pattern
             FROM letter_types lt
             JOIN numbering_rules nr ON nr.id=lt.numbering_rule_id
             WHERE lt.is_active=1 AND nr.is_active=1
             ORDER BY lt.name'
        )->fetchAll();
    }

    public static function letterType(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT lt.id,lt.code,lt.name,nr.id AS rule_id,nr.code AS rule_code,nr.name AS rule_name,nr.unit_code,nr.pattern
             FROM letter_types lt
             JOIN numbering_rules nr ON nr.id=lt.numbering_rule_id
             WHERE lt.id=? AND lt.is_active=1 AND nr.is_active=1 LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function sensitivities(): array
    {
        return Database::connection()->query(
            "SELECT code,name,prefix FROM letter_sensitivities
             WHERE is_active=1 AND code IN ('BIASA','RAHASIA','SANGAT_RAHASIA')
             ORDER BY sort_order"
        )->fetchAll();
    }

    public static function archiveTypes(): array
    {
        return Database::connection()->query('SELECT code,name,short_code FROM archive_types WHERE is_active=1 ORDER BY name')->fetchAll();
    }

    public static function classificationGroups(string $archiveType): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT code,name,archive_type FROM classification_groups
             WHERE archive_type=? AND is_active=1 ORDER BY sort_order,code'
        );
        $stmt->execute([$archiveType]);
        return $stmt->fetchAll();
    }

    public static function classificationItems(string $groupCode): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT code,raw_code,name,description FROM classification_items
             WHERE group_code=? AND is_active=1 ORDER BY sort_order,id'
        );
        $stmt->execute([$groupCode]);
        return $stmt->fetchAll();
    }

    public static function searchClassifications(string $query, ?string $archiveType = null, ?string $groupCode = null): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) return [];

        $where = ['cg.is_active=1', 'ci.is_active=1'];
        $params = [];
        if ($archiveType && in_array($archiveType, ['FASILITATIF','SUBSTANTIF'], true)) {
            $where[] = 'cg.archive_type=:archive';
            $params['archive'] = $archiveType;
        }
        if ($groupCode && preg_match('/^[A-Z]{2,3}$/', $groupCode)) {
            $where[] = 'cg.code=:group_code';
            $params['group_code'] = $groupCode;
        }
        $where[] = '(cg.code LIKE :q1 OR cg.name LIKE :q2 OR ci.code LIKE :q3 OR ci.name LIKE :q4 OR ci.description LIKE :q5)';
        $q = '%' . $query . '%';
        foreach (['q1','q2','q3','q4','q5'] as $key) $params[$key] = $q;

        $sql = 'SELECT cg.code AS group_code,cg.name AS group_name,cg.archive_type,
                       ci.code,ci.name,ci.description
                FROM classification_items ci
                JOIN classification_groups cg ON cg.code=ci.group_code
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY cg.sort_order,ci.sort_order,ci.id LIMIT 30';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function validClassification(string $archiveType, string $groupCode, string $itemCode): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM classification_items ci
             JOIN classification_groups cg ON cg.code=ci.group_code
             WHERE cg.archive_type=? AND cg.is_active=1
               AND ci.group_code=? AND ci.code=? AND ci.is_active=1'
        );
        $stmt->execute([$archiveType,$groupCode,$itemCode]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function classifications(string $scope, ?string $parent = null): array
    {
        $archiveType = str_ends_with($scope, 'F') ? 'FASILITATIF' : 'SUBSTANTIF';
        if ($parent === null) {
            return array_map(fn(array $r) => ['code'=>$r['code'],'name'=>$r['name']], self::classificationGroups($archiveType));
        }
        return array_map(fn(array $r) => ['code'=>$r['code'],'name'=>$r['name'],'description'=>$r['description']], self::classificationItems($parent));
    }

    public static function numberingRules(): array
    {
        return Database::connection()->query(
            'SELECT nr.*,(SELECT COUNT(*) FROM letter_types lt WHERE lt.numbering_rule_id=nr.id) AS type_count
             FROM numbering_rules nr ORDER BY nr.id'
        )->fetchAll();
    }

    public static function adminLetterTypes(): array
    {
        return Database::connection()->query(
            'SELECT lt.id,lt.code,lt.name,lt.numbering_rule_id,lt.is_active,
                    nr.code AS rule_code,nr.name AS rule_name,nr.unit_code,nr.pattern
             FROM letter_types lt JOIN numbering_rules nr ON nr.id=lt.numbering_rule_id
             ORDER BY lt.name'
        )->fetchAll();
    }

    public static function updateNumberingRule(int $id, string $name, string $unitCode, string $pattern, bool $active): void
    {
        $stmt = Database::connection()->prepare('UPDATE numbering_rules SET name=?,unit_code=?,pattern=?,is_active=? WHERE id=?');
        $stmt->execute([$name,$unitCode,$pattern,$active ? 1 : 0,$id]);
    }

    public static function updateLetterTypeRule(int $id, int $ruleId, bool $active): void
    {
        $stmt = Database::connection()->prepare('UPDATE letter_types SET numbering_rule_id=?,is_active=? WHERE id=?');
        $stmt->execute([$ruleId,$active ? 1 : 0,$id]);
    }

    public static function classificationCatalog(array $filters = []): array
    {
        $where=[];$params=[];
        if (!empty($filters['archive_type'])) { $where[]='cg.archive_type=:archive';$params['archive']=$filters['archive_type']; }
        if (!empty($filters['group'])) { $where[]='cg.code=:group_code';$params['group_code']=$filters['group']; }
        if (!empty($filters['q'])) {
            $where[]='(cg.code LIKE :q1 OR cg.name LIKE :q2 OR ci.code LIKE :q3 OR ci.name LIKE :q4)';
            $q='%'.trim($filters['q']).'%';$params['q1']=$q;$params['q2']=$q;$params['q3']=$q;$params['q4']=$q;
        }
        $sql='SELECT cg.code AS group_code,cg.name AS group_name,cg.archive_type,
                     ci.code,ci.raw_code,ci.name,ci.description
              FROM classification_groups cg
              LEFT JOIN classification_items ci ON ci.group_code=cg.code AND ci.is_active=1
              WHERE cg.is_active=1';
        if ($where) $sql.=' AND '.implode(' AND ',$where);
        $sql.=' ORDER BY cg.sort_order,ci.sort_order,ci.id LIMIT 1200';
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function classificationGroupStats(): array
    {
        return Database::connection()->query(
            'SELECT cg.code,cg.name,cg.archive_type,cg.is_active,COUNT(ci.id) AS item_count
             FROM classification_groups cg
             LEFT JOIN classification_items ci ON ci.group_code=cg.code AND ci.is_active=1
             GROUP BY cg.code,cg.name,cg.archive_type,cg.is_active,cg.sort_order
             ORDER BY cg.sort_order,cg.code'
        )->fetchAll();
    }
}
