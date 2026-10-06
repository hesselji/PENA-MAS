<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\DocumentTemplate;
use App\Models\MasterData;
use App\Services\DocxTemplateImporter;
use App\Services\TemplateRenderer;

final class AdminTemplateController
{
    public function index(): void
    {
        Auth::requireAdmin();

        View::render('admin/templates/index', [
            'templates' => DocumentTemplate::all(),
            'letterTypes' => MasterData::letterTypes(),
            'placeholders' => TemplateRenderer::placeholders(),
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();

        View::render('admin/templates/form', [
            'template' => null,
            'letterTypes' => MasterData::letterTypes(),
            'placeholders' => TemplateRenderer::placeholders(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);

        $data = $this->dataFromRequest();
        $errors = $this->validate($data);

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location:/admin/templates/create');
            exit;
        }

        $id = DocumentTemplate::create($data, (int)Auth::id());

        AuditLog::write(
            Auth::id(),
            'CREATE_DOCUMENT_TEMPLATE',
            'document_template',
            $id,
            [
                'name' => $data['name'],
                'source_format' => $data['source_format'],
            ]
        );

        unset($_SESSION['old']);
        $_SESSION['flash_success'] = 'Template dokumen berhasil ditambahkan.';
        header('Location:/admin/templates');
        exit;
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $template = DocumentTemplate::find((int)$id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        /*
         * v3.2:
         * Template DOCX lama yang sempat tersimpan sebagai hasil ekstraksi
         * teks mentah akan diperbaiki otomatis menjadi preview visual rapi.
         * File Word aslinya tidak disentuh.
         */
        if (DocxTemplateImporter::shouldRefreshStoredPreview($template)) {
            $sourcePath = $this->absoluteSourcePath($template);

            if ($sourcePath !== null && is_file($sourcePath)) {
                try {
                    $preview = TemplateRenderer::sanitize(
                        DocxTemplateImporter::import($sourcePath)
                    );

                    if ($preview !== '' && $preview !== (string)$template['content_html']) {
                        DocumentTemplate::updatePreview((int)$template['id'], $preview);
                        $template['content_html'] = $preview;
                    }
                } catch (\Throwable $e) {
                    // Jangan rusak halaman edit hanya karena preview gagal dibuat.
                    // DOCX presisi tetap tersedia dari file sumber asli.
                }
            }
        }

        View::render('admin/templates/form', [
            'template' => $template,
            'letterTypes' => MasterData::letterTypes(),
            'placeholders' => TemplateRenderer::placeholders(),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);

        $template = DocumentTemplate::find((int)$id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $data = $this->dataFromRequest($template);
        $errors = $this->validate($data);

        if ($errors) {
            $_SESSION['flash_error'] = 'Periksa kembali template.';
            header('Location:/admin/templates/' . $id . '/edit');
            exit;
        }

        DocumentTemplate::update((int)$id, $data, (int)Auth::id());

        AuditLog::write(
            Auth::id(),
            'UPDATE_DOCUMENT_TEMPLATE',
            'document_template',
            (int)$id,
            ['name' => $data['name']]
        );

        $_SESSION['flash_success'] = 'Template dokumen diperbarui.';
        header('Location:/admin/templates');
        exit;
    }

    public function toggle(string $id): void
    {
        Auth::requireAdmin();
        Csrf::validate($_POST['_token'] ?? null);

        $template = DocumentTemplate::find((int)$id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $active = !((bool)$template['is_active']);
        DocumentTemplate::setActive((int)$id, $active, (int)Auth::id());

        $_SESSION['flash_success'] = $active
            ? 'Template diaktifkan.'
            : 'Template dinonaktifkan.';

        header('Location:/admin/templates');
        exit;
    }

    public function source(string $id): void
    {
        Auth::requireAdmin();

        $template = DocumentTemplate::find((int)$id);
        if (!$template || empty($template['source_file_path'])) {
            http_response_code(404);
            exit('Berkas sumber tidak tersedia.');
        }

        $path = $this->absoluteSourcePath($template);
        if ($path === null || !is_file($path)) {
            http_response_code(404);
            exit('Berkas sumber tidak tersedia.');
        }

        header('Content-Type: application/octet-stream');
        header(
            'Content-Disposition: attachment; filename="' .
            basename((string)$template['source_file_name']) .
            '"'
        );
        readfile($path);
        exit;
    }

    private function dataFromRequest(?array $current = null): array
    {
        $html = trim($_POST['content_html'] ?? '');
        $sourceName = (string)($current['source_file_name'] ?? '');
        $sourcePath = (string)($current['source_file_path'] ?? '');
        $sourceFormat = (string)($current['source_format'] ?? 'HTML');

        $file = $_FILES['template_file'] ?? null;

        if (
            $file &&
            is_array($file) &&
            ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
        ) {
            $name = (string)($file['name'] ?? '');
            $tmp = (string)($file['tmp_name'] ?? '');
            $size = (int)($file['size'] ?? 0);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($size > 8 * 1024 * 1024) {
                throw new \RuntimeException('Template maksimal 8 MB.');
            }

            if (!in_array($ext, ['html', 'htm', 'txt', 'docx', 'pdf'], true)) {
                throw new \RuntimeException('Format template belum didukung.');
            }

            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            $dir = dirname(__DIR__, 2) . '/storage/template-sources';

            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if (!move_uploaded_file($tmp, $dir . '/' . $safe)) {
                throw new \RuntimeException('Berkas template belum dapat disimpan.');
            }

            $sourceName = $name;
            $sourcePath = 'storage/template-sources/' . $safe;
            $sourceFormat = $ext === 'docx'
                ? 'DOCX_EXACT'
                : strtoupper($ext === 'htm' ? 'HTML' : $ext);

            if ($ext === 'docx') {
                // Preview web yang rapi; file Word asli tetap disimpan utuh.
                $html = DocxTemplateImporter::import($dir . '/' . $safe);
            } elseif (in_array($ext, ['html', 'htm'], true)) {
                $html = (string)file_get_contents($dir . '/' . $safe);
            } elseif ($ext === 'txt') {
                $raw = (string)file_get_contents($dir . '/' . $safe);
                $html = '<div class="doc-body"><p>' .
                    nl2br(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8')) .
                    '</p></div>';
            } elseif ($ext === 'pdf' && $html === '') {
                $html = '<div class="doc-body"><h2>Template dari PDF</h2><p>Berkas PDF disimpan utuh sebagai referensi visual. Untuk dokumen dinamis dengan layout presisi gunakan DOCX Mail Merge/placeholder.</p></div>';
            }
        }

        return [
            'name' => trim($_POST['name'] ?? ''),
            'letter_type_id' => (int)($_POST['letter_type_id'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'content_html' => TemplateRenderer::sanitize($html),
            'source_file_name' => $sourceName,
            'source_file_path' => $sourcePath,
            'source_format' => $sourceFormat,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Nama template wajib diisi.';
        }

        if ($data['content_html'] === '') {
            $errors['content_html'] = 'Isi template wajib diisi.';
        }

        return $errors;
    }

    private function absoluteSourcePath(array $template): ?string
    {
        $relative = trim((string)($template['source_file_path'] ?? ''));
        if ($relative === '') {
            return null;
        }

        return dirname(__DIR__, 2) . '/' . ltrim($relative, '/');
    }
}
