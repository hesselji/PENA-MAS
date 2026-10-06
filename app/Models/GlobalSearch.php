<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class GlobalSearch
{
    public static function run(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return ['outgoing'=>[],'incoming'=>[],'classifications'=>[]];
        $limit = max(1,min(20,$limit));
        $pdo = Database::connection();
        $like = '%' . $query . '%';
        $outgoing = [];
        $incoming = [];
        $classifications = [];

        if (Auth::canAccessOutgoing()) {
            $where = Auth::isAdmin() ? '1=1' : "ol.sensitivity='BIASA'";
            $stmt = $pdo->prepare(
                "SELECT ol.id,ol.letter_number,ol.subject,ol.recipient,ol.sensitivity,ol.letter_date,lt.name letter_type_name
                 FROM outgoing_letters ol JOIN letter_types lt ON lt.id=ol.letter_type_id
                 WHERE {$where} AND (ol.letter_number LIKE ? OR ol.subject LIKE ? OR ol.recipient LIKE ? OR ol.classification_code LIKE ?)
                 ORDER BY ol.id DESC LIMIT {$limit}"
            );
            $stmt->execute([$like,$like,$like,$like]);
            $outgoing = $stmt->fetchAll();

            $stmt = $pdo->prepare(
                "SELECT ci.group_code,ci.code,ci.name,cg.name AS group_name,cg.archive_type
                 FROM classification_items ci JOIN classification_groups cg ON cg.code=ci.group_code
                 WHERE ci.is_active=1 AND cg.is_active=1 AND (ci.code LIKE ? OR ci.name LIKE ? OR ci.description LIKE ? OR cg.name LIKE ?)
                 ORDER BY cg.sort_order,ci.sort_order LIMIT {$limit}"
            );
            $stmt->execute([$like,$like,$like,$like]);
            $classifications = $stmt->fetchAll();
        }

        if (Auth::canAccessIncoming()) {
            $stmt = $pdo->prepare(
                "SELECT id,letter_number,origin,subject,recipient,received_date,workflow_status
                 FROM incoming_letters
                 WHERE letter_number LIKE ? OR origin LIKE ? OR subject LIKE ? OR recipient LIKE ?
                 ORDER BY id DESC LIMIT {$limit}"
            );
            $stmt->execute([$like,$like,$like,$like]);
            $incoming = $stmt->fetchAll();
        }

        return compact('outgoing','incoming','classifications');
    }
}
