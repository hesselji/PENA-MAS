<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Models\MasterData;
use App\Models\OutgoingLetter;
use App\Models\Signer;
use App\Services\ExactDocxService;
use App\Services\PrototypeTteService;

final class DocumentController
{
    public function index(): void
    {
        Auth::requireOutgoingAccess();
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'team' => $_GET['team'] ?? '',
            'sensitivity' => 'BIASA',
            'status' => 'ACTIVE',
            'year' => $_GET['year'] ?? date('Y'),
        ];
        View::render('documents/index', [
            'letters' => OutgoingLetter::search($filters),
            'filters' => $filters,
            'teams' => MasterData::workTeams(),
            'templates' => DocumentTemplate::all(true),
            'signers' => Signer::all(true),
            'recentDocuments' => $this->visibleRecentDocuments(),
        ]);
    }

    public function singleForm(string $letterId): void
    {
        Auth::requireOutgoingAccess();
        $letter = OutgoingLetter::find((int)$letterId);
        if (!$letter) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        if (OutgoingLetter::isConfidential($letter) && (!Auth::isAdmin() || !Auth::hasConfidentialGrant((int)$letterId))) {
            $_SESSION['flash_error'] = 'Verifikasi surat terlindungi terlebih dahulu.';
            header('Location:/letters/' . $letterId);
            exit;
        }
        View::render('documents/single-form', [
            'letter' => $letter,
            'templates' => DocumentTemplate::activeForLetterType((int)$letter['letter_type_id']),
            'signers' => Signer::all(true),
        ]);
    }

    public function generateSingle(string $letterId): void
    {
        Auth::requireOutgoingAccess();
        Csrf::validate($_POST['_token'] ?? null);
        $letter = OutgoingLetter::find((int)$letterId);
        if (!$letter) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        if (OutgoingLetter::isConfidential($letter) && (!Auth::isAdmin() || !Auth::hasConfidentialGrant((int)$letterId))) {
            $_SESSION['flash_error'] = 'Verifikasi ulang diperlukan.';
            header('Location:/letters/' . $letterId);
            exit;
        }
        $template = DocumentTemplate::find((int)($_POST['template_id'] ?? 0), true);
        $signer = Signer::find((int)($_POST['signer_id'] ?? 0), true);
        if (!$template || !$signer) {
            $_SESSION['flash_error'] = 'Pilih template dan penanda tangan aktif.';
            header('Location:/documents/create/' . $letterId);
            exit;
        }
        if ($template['letter_type_id'] !== null && (int)$template['letter_type_id'] !== (int)$letter['letter_type_id']) {
            $_SESSION['flash_error'] = 'Template tidak sesuai jenis surat.';
            header('Location:/documents/create/' . $letterId);
            exit;
        }
        $extras = $this->extras();
        $r = GeneratedDocument::createBatch([$letter], $template, $signer, (int)Auth::id(), 'Dokumen ' . $letter['letter_number'], $extras);
        $docId = (int)$r['document_ids'][0];
        AuditLog::write(Auth::id(), 'GENERATE_DOCUMENT', 'generated_document', $docId, ['letter_id' => (int)$letterId]);
        $_SESSION['flash_success'] = ExactDocxService::canRender($template)
            ? 'Dokumen berhasil dibuat. Template Word asli siap diunduh dalam mode presisi.'
            : 'Dokumen berhasil dibuat.';
        header('Location:/documents/' . $docId);
        exit;
    }

    public function generateBatch(): void
    {
        Auth::requireOutgoingAccess();
        Csrf::validate($_POST['_token'] ?? null);
        $ids = $_POST['letter_ids'] ?? [];
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));
        if (!$ids) {
            $_SESSION['flash_error'] = 'Pilih minimal satu surat.';
            header('Location:/documents');
            exit;
        }
        if (count($ids) > 100) {
            $_SESSION['flash_error'] = 'Maksimal 100 dokumen per batch.';
            header('Location:/documents');
            exit;
        }
        $template = DocumentTemplate::find((int)($_POST['template_id'] ?? 0), true);
        $signer = Signer::find((int)($_POST['signer_id'] ?? 0), true);
        if (!$template || !$signer) {
            $_SESSION['flash_error'] = 'Pilih template dan penanda tangan aktif.';
            header('Location:/documents');
            exit;
        }
        $letters = [];
        foreach ($ids as $id) {
            $letter = OutgoingLetter::find($id);
            if (!$letter || $letter['status'] !== 'ACTIVE' || $letter['sensitivity'] !== 'BIASA') continue;
            if ($template['letter_type_id'] !== null && (int)$template['letter_type_id'] !== (int)$letter['letter_type_id']) continue;
            $letters[] = $letter;
        }
        if (!$letters) {
            $_SESSION['flash_error'] = 'Tidak ada surat yang cocok.';
            header('Location:/documents');
            exit;
        }
        $r = GeneratedDocument::createBatch($letters, $template, $signer, (int)Auth::id(), trim($_POST['batch_label'] ?? ''), $this->extras());
        AuditLog::write(Auth::id(), 'GENERATE_DOCUMENT_BATCH', 'document_batch', (int)$r['batch_id'], ['count' => count($letters)]);
        $_SESSION['flash_success'] = count($letters) . ' dokumen berhasil dibuat.';
        header('Location:/document-batches/' . $r['batch_id']);
        exit;
    }

    public function show(string $id): void
    {
        Auth::requireOutgoingAccess();
        [$doc, $letter] = $this->documentAndLetter((int)$id);
        $doc['display_html'] = PrototypeTteService::decorate((string)$doc['snapshot_html'], $doc);
        $template = DocumentTemplate::find((int)$doc['template_id']);
        View::render('documents/show', [
            'document' => $doc,
            'letter' => $letter,
            'exactDocxAvailable' => $template ? ExactDocxService::canRender($template) : false,
            'exactPdfAvailable' => $template ? (ExactDocxService::canRender($template) && ExactDocxService::pdfConversionAvailable()) : false,
        ]);
    }

    public function submit(string $id): void
    {
        Auth::requireOutgoingAccess();
        Csrf::validate($_POST['_token'] ?? null);
        $d = GeneratedDocument::find((int)$id);
        if (!$d) { http_response_code(404); return; }
        GeneratedDocument::submit((int)$id);
        AuditLog::write(Auth::id(), 'SUBMIT_DOCUMENT_SIGNATURE', 'generated_document', (int)$id);
        $_SESSION['flash_success'] = 'Dokumen diajukan untuk tanda tangan prototype.';
        header('Location:/documents/' . $id);
        exit;
    }

    public function sign(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        $d = GeneratedDocument::find((int)$id);
        if (!$d) { http_response_code(404); return; }
        $s = Signer::find((int)$d['signer_id'], true);
        if (!$s || !Signer::verifyPrototypePasskey($s, (string)($_POST['passkey'] ?? ''))) {
            $_SESSION['flash_error'] = 'Passkey prototype tidak sesuai atau belum diaktifkan.';
            header('Location:/documents/' . $id);
            exit;
        }
        GeneratedDocument::sign((int)$id);
        AuditLog::write(Auth::id(), 'PROTOTYPE_SIGN_DOCUMENT', 'generated_document', (int)$id, ['signer_id' => (int)$s['id']]);
        $_SESSION['flash_success'] = 'Dokumen ditandatangani pada mode prototype.';
        header('Location:/documents/' . $id);
        exit;
    }

    public function reject(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        GeneratedDocument::reject((int)$id, trim($_POST['reason'] ?? ''));
        AuditLog::write(Auth::id(), 'REJECT_DOCUMENT_SIGNATURE', 'generated_document', (int)$id);
        $_SESSION['flash_success'] = 'Pengajuan ditolak.';
        header('Location:/documents/' . $id);
        exit;
    }

    public function batchShow(string $id): void
    {
        Auth::requireOutgoingAccess();
        $b = GeneratedDocument::batchInfo((int)$id);
        $docs = GeneratedDocument::batch((int)$id);
        if (!$b || !$docs) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        $template = DocumentTemplate::find((int)$docs[0]['template_id']);
        View::render('documents/batch', [
            'batch' => $b,
            'documents' => $docs,
            'exactDocxAvailable' => $template ? ExactDocxService::canRender($template) : false,
            'exactPdfAvailable' => $template ? (ExactDocxService::canRender($template) && ExactDocxService::pdfConversionAvailable()) : false,
        ]);
    }

    public function batchSubmit(string $id): void
    {
        Auth::requireOutgoingAccess();
        Csrf::validate($_POST['_token'] ?? null);
        GeneratedDocument::submitBatch((int)$id);
        AuditLog::write(Auth::id(), 'SUBMIT_DOCUMENT_BATCH_SIGNATURE', 'document_batch', (int)$id);
        $_SESSION['flash_success'] = 'Batch diajukan untuk tanda tangan prototype.';
        header('Location:/document-batches/' . $id);
        exit;
    }

    public function batchSign(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        $docs = GeneratedDocument::batch((int)$id);
        if (!$docs) { http_response_code(404); return; }
        $signerIds = array_values(array_unique(array_map(static fn($d) => (int)$d['signer_id'], $docs)));
        if (count($signerIds) !== 1) {
            $_SESSION['flash_error'] = 'Batch harus menggunakan satu penanda tangan.';
            header('Location:/document-batches/' . $id);
            exit;
        }
        $s = Signer::find($signerIds[0], true);
        if (!$s || !Signer::verifyPrototypePasskey($s, (string)($_POST['passkey'] ?? ''))) {
            $_SESSION['flash_error'] = 'Passkey prototype tidak sesuai.';
            header('Location:/document-batches/' . $id);
            exit;
        }
        $count = GeneratedDocument::signBatch((int)$id);
        AuditLog::write(Auth::id(), 'PROTOTYPE_SIGN_DOCUMENT_BATCH', 'document_batch', (int)$id, ['count' => $count]);
        $_SESSION['flash_success'] = $count . ' dokumen ditandatangani dengan satu passkey.';
        header('Location:/document-batches/' . $id);
        exit;
    }

    public function batchReject(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);
        $count = GeneratedDocument::rejectBatch((int)$id, trim($_POST['reason'] ?? ''));
        $_SESSION['flash_success'] = $count . ' dokumen ditolak.';
        header('Location:/document-batches/' . $id);
        exit;
    }

    public function printSingle(string $id): void
    {
        Auth::requireOutgoingAccess();
        [$doc, $letter] = $this->documentAndLetter((int)$id);
        $doc['display_html'] = PrototypeTteService::decorate((string)$doc['snapshot_html'], $doc);
        GeneratedDocument::markPrinted((int)$id);
        $documents = [$doc];
        $batch = null;
        require dirname(__DIR__) . '/Views/documents/print.php';
        exit;
    }

    public function batchPrint(string $id): void
    {
        Auth::requireOutgoingAccess();
        $batch = GeneratedDocument::batchInfo((int)$id);
        $documents = GeneratedDocument::batch((int)$id);
        if (!$batch || !$documents) { http_response_code(404); exit('Batch tidak ditemukan.'); }
        foreach ($documents as &$d) $d['display_html'] = PrototypeTteService::decorate((string)$d['snapshot_html'], $d);
        unset($d);
        GeneratedDocument::markPrintedByBatch((int)$id);
        require dirname(__DIR__) . '/Views/documents/print.php';
        exit;
    }

    public function downloadExactDocx(string $id): void
    {
        Auth::requireOutgoingAccess();
        [$doc, $letter] = $this->documentAndLetter((int)$id);
        $template = DocumentTemplate::find((int)$doc['template_id']);
        $signer = Signer::find((int)$doc['signer_id']);
        if (!$template || !$signer || !ExactDocxService::canRender($template)) {
            $_SESSION['flash_error'] = 'Template ini tidak memiliki sumber DOCX presisi.';
            header('Location:/documents/' . $id);
            exit;
        }
        $path = ExactDocxService::renderToTemp($template, $letter, $signer, $this->documentExtras($doc), $doc);
        AuditLog::write(Auth::id(), 'DOWNLOAD_EXACT_DOCX', 'generated_document', (int)$id);
        $this->streamFile($path, ExactDocxService::docxMime(), $this->safeName($letter['letter_number']) . '.docx', true);
    }

    public function downloadExactPdf(string $id): void
    {
        Auth::requireOutgoingAccess();
        [$doc, $letter] = $this->documentAndLetter((int)$id);
        $template = DocumentTemplate::find((int)$doc['template_id']);
        $signer = Signer::find((int)$doc['signer_id']);
        if (!$template || !$signer || !ExactDocxService::canRender($template)) {
            $_SESSION['flash_error'] = 'Template ini tidak memiliki sumber DOCX presisi.';
            header('Location:/documents/' . $id);
            exit;
        }
        try {
            $docx = ExactDocxService::renderToTemp($template, $letter, $signer, $this->documentExtras($doc), $doc);
            $pdf = ExactDocxService::convertToPdf($docx);
            @unlink($docx);
            AuditLog::write(Auth::id(), 'DOWNLOAD_EXACT_PDF', 'generated_document', (int)$id);
            $this->streamFile($pdf, 'application/pdf', $this->safeName($letter['letter_number']) . '.pdf', true);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location:/documents/' . $id);
            exit;
        }
    }

    public function downloadBatchExactDocx(string $id): void
    {
        Auth::requireOutgoingAccess();
        $batch = GeneratedDocument::batchInfo((int)$id);
        $docs = GeneratedDocument::batch((int)$id);
        if (!$batch || !$docs) { http_response_code(404); exit('Batch tidak ditemukan.'); }

        $zipPath = dirname(__DIR__, 2) . '/storage/generated-exact/batch_' . (int)$id . '_' . bin2hex(random_bytes(6)) . '.zip';
        if (!is_dir(dirname($zipPath))) mkdir(dirname($zipPath), 0775, true);
        $archive = $this->newZipArchive($zipPath);
        $tempFiles = [];
        try {
            foreach ($docs as $doc) {
                $letter = OutgoingLetter::find((int)$doc['outgoing_letter_id']);
                $template = DocumentTemplate::find((int)$doc['template_id']);
                $signer = Signer::find((int)$doc['signer_id']);
                if (!$letter || !$template || !$signer || !ExactDocxService::canRender($template)) continue;
                $path = ExactDocxService::renderToTemp($template, $letter, $signer, $this->documentExtras($doc), $doc);
                $tempFiles[] = $path;
                $name = $this->safeName($letter['letter_number']) . '.docx';
                $this->zipAdd($archive, $name, $path);
            }
            $this->zipClose($archive);
        } finally {
            foreach ($tempFiles as $f) @unlink($f);
        }
        if (!is_file($zipPath) || filesize($zipPath) < 50) {
            @unlink($zipPath);
            $_SESSION['flash_error'] = 'Tidak ada DOCX presisi yang dapat dibuat dari batch ini.';
            header('Location:/document-batches/' . $id);
            exit;
        }
        AuditLog::write(Auth::id(), 'DOWNLOAD_EXACT_DOCX_BATCH', 'document_batch', (int)$id, ['count' => count($docs)]);
        $this->streamFile($zipPath, 'application/zip', 'PENA-MAS-Batch-' . (int)$id . '-DOCX-Presisi.zip', true);
    }

    public function verify(string $token): void
    {
        $doc = GeneratedDocument::findByToken($token);
        if (!$doc) {
            http_response_code(404);
            echo '<h2>Dokumen tidak ditemukan atau belum ditandatangani.</h2>';
            return;
        }
        require dirname(__DIR__) . '/Views/verify/show.php';
        exit;
    }

    private function extras(): array
    {
        return [
            'PEMBEBANAN' => trim($_POST['pembebanan'] ?? ''),
            'DASAR_PEMBEBANAN' => trim($_POST['dasar_pembebanan'] ?? ''),
            'TEMPAT_TTD' => trim($_POST['tempat_ttd'] ?? 'Palangka Raya'),
            'KETERANGAN_TUGAS' => trim($_POST['keterangan_tugas'] ?? ''),
            'TGL_PELAKSANAAN' => trim($_POST['tgl_pelaksanaan'] ?? ''),
            'TUJUAN_TUGAS' => trim($_POST['tujuan_tugas'] ?? ''),
        ];
    }

    private function documentExtras(array $doc): array
    {
        $raw = $doc['custom_data_json'] ?? null;
        if (!$raw) return [];
        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    private function documentAndLetter(int $id): array
    {
        $doc = GeneratedDocument::find($id);
        if (!$doc) { http_response_code(404); View::render('errors/404'); exit; }
        $letter = OutgoingLetter::find((int)$doc['outgoing_letter_id']);
        if (!$letter) { http_response_code(404); View::render('errors/404'); exit; }
        if (OutgoingLetter::isConfidential($letter) && (!Auth::isAdmin() || !Auth::hasConfidentialGrant((int)$letter['id']))) {
            $_SESSION['flash_error'] = 'Verifikasi surat terlindungi terlebih dahulu.';
            header('Location:/letters/' . $letter['id']);
            exit;
        }
        return [$doc, $letter];
    }

    private function visibleRecentDocuments(): array
    {
        $r = GeneratedDocument::recent(40);
        if (Auth::isAdmin()) return $r;
        return array_values(array_filter($r, static fn($x) => $x['sensitivity'] === 'BIASA'));
    }

    private function safeName(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?? 'dokumen';
        return trim($value, '._-') ?: 'dokumen';
    }

    private function streamFile(string $path, string $mime, string $filename, bool $deleteAfter = false): never
    {
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, no-store, max-age=0');
        readfile($path);
        if ($deleteAfter) @unlink($path);
        exit;
    }

    private function newZipArchive(string $path): array
    {
        @unlink($path);
        if (class_exists('\\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Arsip batch belum dapat dibuat.');
            }
            return ['type' => 'zip', 'object' => $zip];
        }
        if (class_exists('\\PharData')) {
            return ['type' => 'phar', 'object' => new \PharData($path, 0, null, \Phar::ZIP)];
        }
        throw new \RuntimeException('Server belum menyediakan pembuat ZIP.');
    }

    private function zipAdd(array &$archive, string $name, string $path): void
    {
        if ($archive['type'] === 'zip') {
            $archive['object']->addFile($path, $name);
        } else {
            $archive['object']->addFile($path, $name);
        }
    }

    private function zipClose(array &$archive): void
    {
        if ($archive['type'] === 'zip') $archive['object']->close();
        unset($archive['object']);
    }
}
