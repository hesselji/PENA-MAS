<?php
namespace App\Services;

use App\Core\Env;

final class ExactDocxService
{
    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public static function canRender(array $template): bool
    {
        $format = strtoupper((string)($template['source_format'] ?? ''));
        if (!in_array($format, ['DOCX', 'DOCX_EXACT'], true)) return false;
        $path = self::absoluteTemplatePath($template);
        return $path !== null && is_file($path);
    }

    public static function docxMime(): string
    {
        return self::DOCX_MIME;
    }

    public static function renderToTemp(array $template, array $letter, array $signer, array $extras = [], ?array $document = null): string
    {
        $source = self::absoluteTemplatePath($template);
        if ($source === null || !is_file($source)) {
            throw new \RuntimeException('Berkas sumber DOCX presisi tidak tersedia.');
        }

        $tmpDir = dirname(__DIR__, 2) . '/storage/generated-exact';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException('Folder dokumen presisi belum dapat dibuat.');
        }

        $target = $tmpDir . '/penamas_' . bin2hex(random_bytes(10)) . '.docx';
        if (!copy($source, $target)) {
            throw new \RuntimeException('Template DOCX belum dapat disalin.');
        }

        $values = self::values($letter, $signer, $extras, $document);
        self::patchDocx($target, $values);
        return $target;
    }

    public static function values(array $letter, array $signer, array $extras = [], ?array $document = null): array
    {
        $snapshot = [];
        if (!empty($extras['_snapshot']) && is_array($extras['_snapshot'])) $snapshot = $extras['_snapshot'];
        $letterSnap = is_array($snapshot['letter'] ?? null) ? $snapshot['letter'] : [];
        $signerSnap = is_array($snapshot['signer'] ?? null) ? $snapshot['signer'] : [];

        $letter = array_merge($letter, array_filter($letterSnap, static fn($v) => $v !== null));
        $signer = array_merge($signer, array_filter($signerSnap, static fn($v) => $v !== null));

        $date = (string)($letter['letter_date'] ?? '');
        $longDate = self::longDate($date);
        $year = $date !== '' ? date('Y', strtotime($date)) : date('Y');
        $purpose = trim((string)($extras['KETERANGAN_TUGAS'] ?? ''));
        if ($purpose === '') $purpose = trim((string)($letter['subject'] ?? ''));

        $taskDate = trim((string)($extras['TGL_PELAKSANAAN'] ?? ''));
        $taskDestination = trim((string)($extras['TUJUAN_TUGAS'] ?? ''));
        $budgetCode = trim((string)($extras['PEMBEBANAN'] ?? ''));
        $budgetBasis = trim((string)($extras['DASAR_PEMBEBANAN'] ?? ''));
        if ($budgetBasis === '') $budgetBasis = 'DIPA BPS Kota Palangka Raya T.A ' . $year;
        $place = trim((string)($extras['TEMPAT_TTD'] ?? 'Palangka Raya')) ?: 'Palangka Raya';

        $verification = '';
        if (($document['tte_status'] ?? '') === 'SIGNED' && !empty($document['tte_reference'])) {
            $verification = '/verify/' . $document['tte_reference'];
        }

        return [
            // PENA MAS placeholders
            '{{NOMOR_SURAT}}' => (string)($letter['letter_number'] ?? ''),
            '{{TANGGAL_SURAT}}' => $longDate,
            '{{TANGGAL_SURAT_LONG}}' => $longDate,
            '{{PERIHAL}}' => (string)($letter['subject'] ?? ''),
            '{{TUJUAN}}' => (string)($letter['recipient'] ?? ''),
            '{{KKA}}' => (string)($letter['classification_code'] ?? ''),
            '{{JENIS_SURAT}}' => (string)($letter['letter_type_name'] ?? ''),
            '{{TIM_KERJA}}' => (string)($letter['work_team_name'] ?? ''),
            '{{CATATAN}}' => (string)($letter['notes'] ?? ''),
            '{{PEMBEBANAN}}' => $budgetCode,
            '{{DASAR_PEMBEBANAN}}' => $budgetBasis,
            '{{TEMPAT_TTD}}' => $place,
            '{{KETERANGAN_TUGAS}}' => $purpose,
            '{{TGL_PELAKSANAAN}}' => $taskDate,
            '{{TUJUAN_TUGAS}}' => $taskDestination,
            '{{NAMA_PENANDA_TANGAN}}' => (string)($signer['name'] ?? ''),
            '{{JABATAN_PENANDA_TANGAN}}' => (string)($signer['position'] ?? ''),
            '{{NIP_PENANDA_TANGAN}}' => (string)($signer['nip'] ?? ''),
            '{{QR_VERIFIKASI}}' => $verification,

            // Word Mail Merge fields pada contoh Pak Citra
            'No_SURAT' => (string)($letter['letter_number'] ?? ''),
            'Pembebanan' => $budgetBasis,
            'Nama_tulis_sesuai_sheet_Data_Pegawai_j' => (string)($letter['recipient'] ?? ''),
            'Keperluan' => $purpose,
            'Tgl_Pelaksanaan' => $taskDate,
            'Tujuan' => $taskDestination,
            'Mata_AnggaranPembebanan' => $budgetCode,
            'Tgl_Surat' => $longDate,

            // Anchor contoh Pak Citra yang bukan MERGEFIELD
            '__SAMPLE_SIGNER_NAME__' => (string)($signer['name'] ?? ''),
            '__SAMPLE_SIGNER_POSITION__' => (string)($signer['position'] ?? ''),
        ];
    }

    public static function pdfConversionAvailable(): bool
    {
        return self::officeBinary() !== null && self::shellAllowed();
    }

    public static function convertToPdf(string $docxPath): string
    {
        $office = self::officeBinary();
        if ($office === null || !self::shellAllowed()) {
            throw new \RuntimeException('Server ini belum menyediakan konversi DOCX ke PDF presisi. Gunakan DOCX Presisi lalu pilih Simpan sebagai PDF dari Microsoft Word/LibreOffice.');
        }
        $outDir = dirname($docxPath);
        $cmd = escapeshellarg($office) . ' --headless --convert-to pdf --outdir ' . escapeshellarg($outDir) . ' ' . escapeshellarg($docxPath) . ' 2>&1';
        @shell_exec($cmd);
        $pdf = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        if (!$pdf || !is_file($pdf) || filesize($pdf) < 100) {
            throw new \RuntimeException('Konversi PDF presisi belum berhasil pada server ini.');
        }
        return $pdf;
    }

    private static function patchDocx(string $path, array $values): void
    {
        $entries = self::xmlEntries($path);
        foreach ($entries as $entry) {
            $xml = self::readEntry($path, $entry);
            if ($xml === null || $xml === '') continue;
            $patched = self::patchXml($xml, $values);
            if ($patched !== $xml) self::writeEntry($path, $entry, $patched);
        }
    }

    private static function patchXml(string $xml, array $values): string
    {
        // 1) Materialize MERGEFIELD results while retaining the exact result-run formatting.
        $fieldPattern = '~<w:fldChar[^>]*w:fldCharType="begin"[^>]*/>.*?<w:instrText[^>]*>\\s*MERGEFIELD\\s+(?:"|&quot;)?([^"<&]+?)(?:"|&quot;)?\\s*</w:instrText>.*?<w:fldChar[^>]*w:fldCharType="separate"[^>]*/>(.*?)<w:fldChar[^>]*w:fldCharType="end"[^>]*/>~s';
        $xml = preg_replace_callback($fieldPattern, static function (array $m) use ($values): string {
            $field = trim(html_entity_decode((string)$m[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
            if (!array_key_exists($field, $values)) return $m[0];
            return self::replaceResultRuns((string)$m[2], (string)$values[$field]);
        }, $xml) ?? $xml;

        // 2) Replace explicit {{PLACEHOLDER}} text for templates prepared manually in Word.
        foreach ($values as $key => $value) {
            if (!str_starts_with((string)$key, '{{')) continue;
            $xml = str_replace(self::xmlEscape((string)$key), self::xmlEscape((string)$value), $xml);
            $xml = str_replace((string)$key, self::xmlEscape((string)$value), $xml);
        }

        // 3) Example-document anchors that are ordinary text, not merge fields.
        if (($values['__SAMPLE_SIGNER_NAME__'] ?? '') !== '') {
            $xml = str_replace('Amos Adam Residul', self::xmlEscape((string)$values['__SAMPLE_SIGNER_NAME__']), $xml);
        }

        $position = trim((string)($values['__SAMPLE_SIGNER_POSITION__'] ?? ''));
        if ($position !== '') {
            // The supplied BPS template uses a two-line Kepala block. Preserve it when the chosen signer is Kepala.
            if (stripos($position, 'Kepala Badan Pusat Statistik') === false) {
                $xml = str_replace('Kepala Badan Pusat Statistik', self::xmlEscape($position), $xml);
                $xml = str_replace('Kota Palangka Raya,', '', $xml);
            }
        }

        // Prevent Word from reconnecting to the original mail-merge data source.
        $xml = preg_replace('~<w:mailMerge>.*?</w:mailMerge>~s', '', $xml) ?? $xml;
        return $xml;
    }

    private static function replaceResultRuns(string $resultXml, string $value): string
    {
        $done = false;
        $resultXml = preg_replace_callback('~<w:t([^>]*)>.*?</w:t>~s', static function (array $m) use (&$done, $value): string {
            $attrs = preg_replace('/\\s+xml:space="[^"]*"/', '', (string)$m[1]) ?? (string)$m[1];
            if (!$done) {
                $done = true;
                return '<w:t' . $attrs . ' xml:space="preserve">' . self::xmlEscape($value) . '</w:t>';
            }
            return '<w:t' . $attrs . '></w:t>';
        }, $resultXml) ?? $resultXml;
        if (!$done) {
            $resultXml = '<w:r><w:t xml:space="preserve">' . self::xmlEscape($value) . '</w:t></w:r>' . $resultXml;
        }
        return $resultXml;
    }

    private static function xmlEntries(string $path): array
    {
        $names = [];
        if (class_exists('\\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = (string)$zip->getNameIndex($i);
                    if (preg_match('~^word/(document|header[0-9]*|footer[0-9]*)\\.xml$~', $name)) $names[] = $name;
                    if ($name === 'word/settings.xml') $names[] = $name;
                }
                $zip->close();
            }
        } elseif (class_exists('\\PharData')) {
            $phar = new \PharData($path);
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                $name = str_replace('phar://' . $path . '/', '', $file->getPathname());
                if (preg_match('~^word/(document|header[0-9]*|footer[0-9]*)\\.xml$~', $name) || $name === 'word/settings.xml') $names[] = $name;
            }
            unset($phar);
        } else {
            throw new \RuntimeException('Server belum menyediakan pembaca arsip DOCX (ZipArchive/PharData).');
        }
        return array_values(array_unique($names));
    }

    private static function readEntry(string $path, string $entry): ?string
    {
        if (class_exists('\\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) return null;
            $data = $zip->getFromName($entry);
            $zip->close();
            return is_string($data) ? $data : null;
        }
        if (class_exists('\\PharData')) {
            $phar = new \PharData($path);
            if (!isset($phar[$entry])) { unset($phar); return null; }
            $data = $phar[$entry]->getContent();
            unset($phar);
            return $data;
        }
        return null;
    }

    private static function writeEntry(string $path, string $entry, string $data): void
    {
        if (class_exists('\\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) throw new \RuntimeException('DOCX presisi tidak dapat dibuka untuk ditulis.');
            $zip->addFromString($entry, $data);
            $zip->close();
            return;
        }
        if (class_exists('\\PharData')) {
            $phar = new \PharData($path);
            $phar[$entry] = $data;
            unset($phar);
            return;
        }
        throw new \RuntimeException('Server belum menyediakan penulis arsip DOCX.');
    }

    private static function absoluteTemplatePath(array $template): ?string
    {
        $relative = trim((string)($template['source_file_path'] ?? ''));
        if ($relative === '') return null;
        return dirname(__DIR__, 2) . '/' . ltrim($relative, '/');
    }

    private static function longDate(string $date): string
    {
        if ($date === '' || strtotime($date) === false) return '';
        $ts = strtotime($date);
        $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        return (int)date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function shellAllowed(): bool
    {
        if (!function_exists('shell_exec')) return false;
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        return !in_array('shell_exec', $disabled, true);
    }

    private static function officeBinary(): ?string
    {
        $configured = trim((string)Env::get('OFFICE_BIN', ''));
        if ($configured !== '' && is_file($configured)) return $configured;
        if (!self::shellAllowed()) return null;
        foreach (['libreoffice', 'soffice'] as $cmd) {
            $found = trim((string)@shell_exec('command -v ' . $cmd . ' 2>/dev/null'));
            if ($found !== '') return $found;
        }
        return null;
    }
}
