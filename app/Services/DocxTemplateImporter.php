<?php
namespace App\Services;

final class DocxTemplateImporter
{
    /**
     * Membuat PREVIEW WEB dari DOCX.
     *
     * Penting:
     * - Preview web hanya untuk editor/pengecekan cepat.
     * - File DOCX asli tetap dipakai oleh ExactDocxService untuk hasil presisi.
     * - Untuk template Surat Tugas Pak Citra, gunakan preview terstruktur agar
     *   tampilannya tetap rapi seperti versi editor visual sebelumnya.
     */
    public static function import(string $path): string
    {
        if (!is_file($path)) {
            throw new \RuntimeException('DOCX tidak ditemukan.');
        }

        $xml = self::readEntry($path, 'word/document.xml');
        if (!$xml) {
            return self::fallbackPreview();
        }

        if (self::looksLikePakCitraTaskLetter($xml)) {
            return self::pakCitraPreview();
        }

        return self::genericPreview($xml);
    }

    /**
     * Dipakai controller untuk memperbaiki otomatis preview lama yang pernah
     * tersimpan sebagai teks mentah/angka hasil ekstraksi DOCX.
     */
    public static function shouldRefreshStoredPreview(array $template): bool
    {
        $format = strtoupper((string)($template['source_format'] ?? ''));
        $sourceName = strtolower((string)($template['source_file_name'] ?? ''));
        $sourcePath = strtolower((string)($template['source_file_path'] ?? ''));
        $isDocx = in_array($format, ['DOCX', 'DOCX_EXACT'], true)
            || str_ends_with($sourceName, '.docx')
            || str_ends_with($sourcePath, '.docx');

        if (!$isDocx) {
            return false;
        }

        $html = trim((string)($template['content_html'] ?? ''));
        if ($html === '') {
            return true;
        }

        if (str_contains($html, 'Preview ringkas DOCX')) {
            return true;
        }

        if (str_contains($html, 'class="docx-imported"')) {
            return true;
        }

        // Preview lama yang bermasalah pada template contoh sering berisi
        // deretan angka/teks metadata XML di awal hasil ekstraksi.
        if (preg_match('/\b(?:2998470|1724025|1786255)\d+/u', strip_tags($html))) {
            return true;
        }

        return false;
    }

    /**
     * Preview HTML rapi untuk contoh Surat Tugas Pak Citra.
     * Layout DOCX final TIDAK diambil dari HTML ini.
     */
    public static function pakCitraPreview(): string
    {
        return <<<'HTML'
<div class="bps-task-template">
    <div class="bps-title">
        <img class="bps-doc-logo" src="/assets/images/bps-logo-surat.jpg">
        <h2>SURAT TUGAS</h2>
        <p>Nomor : {{NOMOR_SURAT}}</p>
    </div>

    <div class="bps-task-grid">
        <div class="label">Menimbang</div>
        <div>:</div>
        <div>Bahwa dalam rangka pencapaian Indikator Kinerja Utama dan penguatan tata kelola pemerintahan yang baik di lingkungan Badan Pusat Statistik, maka perlu penugasan sebagai berikut:</div>

        <div class="label">Mengingat</div>
        <div>:</div>
        <div>
            <ol>
                <li>Undang-Undang No.16 Tahun 1997 tentang Statistik;</li>
                <li>Peraturan Presiden Nomor 1 Tahun 2025 tentang Perubahan atas Peraturan Presiden Nomor 86 Tahun 2007 tentang Badan Pusat Statistik;</li>
                <li>Peraturan Pemerintah No. 51 Tahun 1999 tentang Penyelenggaraan Statistik;</li>
                <li>Keputusan Presiden RI No. 42 Tahun 2002 tentang Pedoman Pelaksanaan APBN;</li>
                <li>Peraturan Menteri Keuangan No. 113/PMK.05/2012 tentang Perjalanan Dinas Dalam Negeri Bagi Pejabat Negara, Pegawai Negeri, dan Pegawai Tidak Tetap;</li>
                <li>Perpres No. 39 Tahun 2019 tentang Satu Data Indonesia;</li>
                <li>Peraturan Kepala Badan Pusat Statistik No. 103 tahun 2014 tentang pelaksanaan perjalanan dinas jabatan di lingkungan Badan Pusat Statistik;</li>
                <li>Semua biaya yang timbul dengan diterbitkannya Surat Tugas ini dibebankan kepada {{DASAR_PEMBEBANAN}}.</li>
            </ol>
        </div>
    </div>

    <div class="bps-command">Memberi Perintah</div>

    <div class="bps-task-grid">
        <div>Nama</div>
        <div>:</div>
        <div>{{TUJUAN}}</div>

        <div>Untuk</div>
        <div>:</div>
        <div>{{KETERANGAN_TUGAS}} {{TGL_PELAKSANAAN}} {{TUJUAN_TUGAS}}</div>

        <div>Pembebanan</div>
        <div>:</div>
        <div>{{PEMBEBANAN}}</div>
    </div>

    <div class="bps-signature">
        <p>{{TEMPAT_TTD}}, {{TANGGAL_SURAT_LONG}}</p>
        <p>{{JABATAN_PENANDA_TANGAN}},</p>
        <br><br>
        <strong>{{NAMA_PENANDA_TANGAN}}</strong>
        <p>{{NIP_PENANDA_TANGAN}}</p>
        {{QR_VERIFIKASI}}
    </div>

    <div class="bps-integrity-footer">
        Dalam rangka menjaga keberlanjutan Zona Integritas Wilayah Bebas dari Korupsi (WBK) menuju Wilayah Birokrasi Bersih Melayani (WBBM),<br>
        BPS Kota Palangka Raya berkomitmen untuk selalu menjaga integritas dan memegang teguh nilai-nilai ASN BerAKHLAK.<br>
        Apabila dalam pelayanan kami terdapat penyimpangan/pelanggaran kode etik, mohon berkenan melaporkan melalui lapor.go.id atau Whatsapp SiRentas 0858-1000-6271
    </div>

    <div class="page-break"></div>

    <div class="bps-attachment-title">Lampiran Surat Tugas</div>
    <p>Nomor : {{NOMOR_SURAT}}<br>Tanggal : {{TANGGAL_SURAT_LONG}}</p>

    <div class="bps-signature">
        <p>Mengetahui,<br>{{JABATAN_PENANDA_TANGAN}},</p>
        <br>
        <strong>{{NAMA_PENANDA_TANGAN}}</strong>
    </div>

    <table class="travel-grid">
        <tbody>
            <tr><td>1. Pada Tanggal :<br>...............................<br><br>Tiba Di :<br>.............................</td><td>8. Pada Tanggal :<br>............................<br><br>Tiba Di :<br>.............................</td></tr>
            <tr><td>2. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>............................</td><td>9. Pada Tanggal :<br>............................<br><br>Tiba Di :<br>............................</td></tr>
            <tr><td>3. Pada Tanggal :<br>............................<br><br>Tiba Di :<br>.............................</td><td>10. Pada Tanggal :<br>............................<br><br>Tiba Di :<br>............................</td></tr>
            <tr><td>4. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td><td>11. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td></tr>
            <tr><td>5. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td><td>12. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td></tr>
            <tr><td>6. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td><td>13. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td></tr>
            <tr><td>7. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td><td>14. Pada Tanggal :<br>.............................<br><br>Tiba Di :<br>.............................</td></tr>
        </tbody>
    </table>
</div>
HTML;
    }

    private static function looksLikePakCitraTaskLetter(string $xml): bool
    {
        $plain = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        $signals = [
            'SURAT TUGAS',
            'Memberi Perintah',
            'Taupikurahman',
            '2910.QMA.007.005.521213',
            'BPS Kota Palangka Raya',
        ];

        $matched = 0;
        foreach ($signals as $signal) {
            if (stripos($plain, $signal) !== false || stripos($xml, $signal) !== false) {
                $matched++;
            }
        }

        // Mail Merge field unik pada file contoh.
        if (stripos($xml, 'Nama_tulis_sesuai_sheet_Data_Pegawai_j') !== false) {
            $matched += 2;
        }
        if (stripos($xml, 'Mata_AnggaranPembebanan') !== false) {
            $matched += 2;
        }

        return $matched >= 3;
    }

    private static function genericPreview(string $xml): string
    {
        // Hanya untuk DOCX umum yang belum punya preset preview khusus.
        $xml = preg_replace('/<w:tab[^>]*\/>/i', "\t", $xml) ?? $xml;
        $xml = preg_replace('/<w:br[^>]*\/>/i', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:p>/i', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tc>/i', "\t", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tr>/i', "\n", $xml) ?? $xml;

        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        $lines = preg_split('/\R/u', trim($text)) ?: [];

        $html = '<div class="doc-body docx-generic-preview">';
        $html .= '<div class="precision-note"><strong>Pratinjau isi DOCX</strong><p>Hasil final tetap memakai file Word asli melalui DOCX Presisi.</p></div>';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $html .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $html .= '</div>';
        return self::prototypeMappings($html);
    }

    private static function fallbackPreview(): string
    {
        return '<div class="doc-body"><h2>Template DOCX Presisi</h2><p>Berkas Word tersimpan utuh. Pratinjau web belum dapat dibangun, tetapi file Word asli tetap digunakan saat membuat DOCX Presisi.</p></div>';
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
            try {
                $phar = new \PharData($path);
                if (!isset($phar[$entry])) {
                    unset($phar);
                    return null;
                }
                $data = $phar[$entry]->getContent();
                unset($phar);
                return $data;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private static function prototypeMappings(string $html): string
    {
        return strtr($html, [
            'B-3151/62710/VS.300/2025' => '{{NOMOR_SURAT}}',
            'Taupikurahman' => '{{TUJUAN}}',
            '2910.QMA.007.005.521213' => '{{PEMBEBANAN}}',
            '15 Desember 2025' => '{{TANGGAL_SURAT_LONG}}',
            'Amos Adam Residul' => '{{NAMA_PENANDA_TANGAN}}',
        ]);
    }
}
