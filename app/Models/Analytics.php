<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Analytics
{
    public static function dashboard(): array
    {
        $pdo = Database::connection();
        $where = Auth::isAdmin() ? '1=1' : ('ol.requested_by=' . (int)Auth::id() . " AND ol.sensitivity='BIASA'");

        $stmt = $pdo->query(
            "SELECT DATE_FORMAT(ol.letter_date,'%Y-%m') ym,COUNT(*) total
             FROM outgoing_letters ol
             WHERE {$where} AND ol.letter_date >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 5 MONTH)
             GROUP BY DATE_FORMAT(ol.letter_date,'%Y-%m') ORDER BY ym"
        );
        $outgoingRaw = [];
        foreach ($stmt->fetchAll() as $row) $outgoingRaw[$row['ym']] = (int)$row['total'];

        $incomingRaw = [];
        if (Auth::isAdmin()) {
            foreach ($pdo->query(
                "SELECT DATE_FORMAT(received_date,'%Y-%m') ym,COUNT(*) total
                 FROM incoming_letters
                 WHERE received_date >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 5 MONTH)
                 GROUP BY DATE_FORMAT(received_date,'%Y-%m') ORDER BY ym"
            )->fetchAll() as $row) $incomingRaw[$row['ym']] = (int)$row['total'];
        }

        $months = [];
        for ($i=5; $i>=0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} month");
            $ym = date('Y-m', $ts);
            $months[] = [
                'key'=>$ym,
                'label'=>month_year_id($ym . '-01'),
                'short'=>substr(month_year_id($ym . '-01'),0,3),
                'outgoing'=>$outgoingRaw[$ym] ?? 0,
                'incoming'=>$incomingRaw[$ym] ?? 0,
            ];
        }

        $kka = $pdo->query(
            "SELECT ol.classification_code,COUNT(*) total
             FROM outgoing_letters ol
             WHERE {$where}
             GROUP BY ol.classification_code ORDER BY total DESC,ol.classification_code LIMIT 6"
        )->fetchAll();

        $incomingWorkflow = ['MASUK'=>0,'DISPOSISI'=>0,'SELESAI'=>0];
        if (Auth::isAdmin()) {
            foreach ($pdo->query('SELECT workflow_status,COUNT(*) total FROM incoming_letters GROUP BY workflow_status')->fetchAll() as $row) {
                $incomingWorkflow[$row['workflow_status']] = (int)$row['total'];
            }
        }

        return [
            'trendMonths'=>$months,
            'topKka'=>$kka,
            'incomingWorkflow'=>$incomingWorkflow,
        ];
    }
}
