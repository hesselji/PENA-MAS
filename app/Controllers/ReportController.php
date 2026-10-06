<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\MasterData;
use App\Models\Report;

final class ReportController
{
    public function index(): void
    {
        Auth::requireLogin();

        $source = $this->resolveSource($_GET['source'] ?? null);
        $filters = $this->filtersFromRequest($source);

        if ($source === 'incoming') {
            $rows = Report::incoming($filters, 300);
            $summary = Report::incomingSummary($filters);
        } else {
            $rows = Report::outgoing($filters, 300);
            $summary = Report::outgoingSummary($filters);
        }

        View::render('reports/index', [
            'source' => $source,
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'teams' => Auth::canAccessOutgoing() ? MasterData::workTeams() : [],
            'letterTypes' => Auth::canAccessOutgoing() ? MasterData::letterTypes() : [],
            'sensitivities' => Auth::isAdmin() ? MasterData::sensitivities() : [],
            'canOutgoing' => Auth::canAccessOutgoing(),
            'canIncoming' => Auth::canAccessIncoming(),
            'previewLimit' => 300,
        ]);
    }

    public function exportCsv(): void
    {
        Auth::requireLogin();

        $source = $this->resolveSource($_GET['source'] ?? null);
        $filters = $this->filtersFromRequest($source);
        $rows = $source === 'incoming'
            ? Report::incoming($filters, null)
            : Report::outgoing($filters, null);

        AuditLog::write(Auth::id(), 'EXPORT_REPORT_CSV', 'report', null, [
            'source' => $source,
            'row_count' => count($rows),
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ]);

        $suffix = $source === 'incoming' ? 'surat-masuk' : 'surat-keluar';
        $filename = 'laporan-' . $suffix . '-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            throw new \RuntimeException('Tidak dapat menyiapkan file laporan.');
        }

        // BOM agar karakter Indonesia terbaca baik di Microsoft Excel.
        fwrite($out, "\xEF\xBB\xBF");

        if ($source === 'incoming') {
            fputcsv($out, ['No', 'Nomor Surat', 'Tanggal Surat', 'Tanggal Diterima', 'Asal', 'Perihal', 'Kepada', 'Dicatat Oleh']);
            foreach ($rows as $i => $row) {
                fputcsv($out, [
                    $i + 1,
                    $row['letter_number'],
                    $row['letter_date'],
                    $row['received_date'],
                    $row['origin'],
                    $row['subject'],
                    $row['recipient'],
                    display_user_name($row['creator_name']),
                ]);
            }
        } else {
            fputcsv($out, ['No', 'Nomor Surat', 'Tanggal Surat', 'Jenis Surat', 'Tim Kerja', 'Perihal', 'Tujuan', 'KKA', 'Sifat', 'Status', 'Dibuat Oleh']);
            foreach ($rows as $i => $row) {
                $secret = in_array($row['sensitivity'], ['RAHASIA','SANGAT_RAHASIA'], true);
                fputcsv($out, [
                    $i + 1,
                    $row['letter_number'],
                    $row['letter_date'],
                    $row['letter_type_name'],
                    $row['work_team_name'],
                    $secret ? 'Detail terlindungi' : $row['subject'],
                    $secret ? '-' : $row['recipient'],
                    $row['classification_code'],
                    sensitivity_label($row['sensitivity']),
                    status_label($row['status']),
                    display_user_name($row['requester_name']),
                ]);
            }
        }

        fclose($out);
        exit;
    }

    public function printView(): void
    {
        Auth::requireLogin();

        $source = $this->resolveSource($_GET['source'] ?? null);
        $filters = $this->filtersFromRequest($source);
        $rows = $source === 'incoming'
            ? Report::incoming($filters, null)
            : Report::outgoing($filters, null);
        $summary = $source === 'incoming'
            ? Report::incomingSummary($filters)
            : Report::outgoingSummary($filters);

        AuditLog::write(Auth::id(), 'OPEN_PRINT_REPORT', 'report', null, [
            'source' => $source,
            'row_count' => count($rows),
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ]);

        $me = Auth::user();
        require dirname(__DIR__) . '/Views/reports/print.php';
        exit;
    }

    private function resolveSource(?string $requested): string
    {
        if (Auth::isIncoming()) {
            return 'incoming';
        }

        if (Auth::isUser()) {
            return 'outgoing';
        }

        // ADMIN dapat memilih kedua jenis laporan.
        return $requested === 'incoming' ? 'incoming' : 'outgoing';
    }

    private function filtersFromRequest(string $source): array
    {
        $dateFrom = $this->validDate($_GET['date_from'] ?? '') ?: date('Y-m-01');
        $dateTo = $this->validDate($_GET['date_to'] ?? '') ?: date('Y-m-d');

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $filters = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'team' => '',
            'letter_type' => '',
            'sensitivity' => '',
            'status' => '',
        ];

        if ($source === 'outgoing') {
            $filters['team'] = (string)($_GET['team'] ?? '');
            $filters['letter_type'] = (string)($_GET['letter_type'] ?? '');
            $filters['status'] = in_array($_GET['status'] ?? '', ['ACTIVE','CANCELLED'], true)
                ? (string)$_GET['status']
                : '';

            if (Auth::isAdmin()) {
                $filters['sensitivity'] = in_array($_GET['sensitivity'] ?? '', ['BIASA','RAHASIA','SANGAT_RAHASIA'], true)
                    ? (string)$_GET['sensitivity']
                    : '';
            } else {
                $filters['sensitivity'] = 'BIASA';
            }
        }

        return $filters;
    }

    private function validDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') return null;

        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
