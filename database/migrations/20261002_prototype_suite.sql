-- PENA MAS v3.0 Prototype Suite
-- Jalankan SATU KALI pada database v2.0 setelah backup.

SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE incoming_letters
    ADD COLUMN attachment_name VARCHAR(255) NULL AFTER notes,
    ADD COLUMN attachment_path VARCHAR(500) NULL AFTER attachment_name,
    ADD COLUMN attachment_mime VARCHAR(120) NULL AFTER attachment_path,
    ADD COLUMN attachment_size BIGINT UNSIGNED NULL AFTER attachment_mime;

CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    nip VARCHAR(80) NULL,
    position VARCHAR(190) NULL,
    work_team_id INT UNSIGNED NULL,
    email VARCHAR(190) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_active (is_active,name),
    INDEX idx_employee_nip (nip),
    CONSTRAINT fk_employee_team FOREIGN KEY(work_team_id) REFERENCES work_teams(id),
    CONSTRAINT fk_employee_creator FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_employee_updater FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE signers
    ADD COLUMN employee_id INT UNSIGNED NULL AFTER work_team_id,
    ADD COLUMN prototype_passkey_hash VARCHAR(255) NULL AFTER employee_id,
    ADD COLUMN prototype_tte_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER prototype_passkey_hash,
    ADD INDEX idx_signer_employee (employee_id),
    ADD CONSTRAINT fk_signer_employee FOREIGN KEY(employee_id) REFERENCES employees(id) ON DELETE SET NULL;

ALTER TABLE document_templates
    ADD COLUMN source_file_name VARCHAR(255) NULL AFTER content_html,
    ADD COLUMN source_file_path VARCHAR(500) NULL AFTER source_file_name,
    ADD COLUMN source_format VARCHAR(30) NOT NULL DEFAULT 'HTML' AFTER source_file_path;

ALTER TABLE generated_documents
    ADD COLUMN custom_data_json JSON NULL AFTER snapshot_html,
    ADD COLUMN submitted_at DATETIME NULL AFTER tte_reference,
    ADD COLUMN signed_at DATETIME NULL AFTER submitted_at,
    ADD COLUMN rejected_at DATETIME NULL AFTER signed_at,
    ADD COLUMN rejection_note TEXT NULL AFTER rejected_at,
    ADD COLUMN signature_hash VARCHAR(64) NULL AFTER rejection_note,
    ADD UNIQUE INDEX uniq_generated_tte_reference (tte_reference);

-- Template contoh dibuat dari dokumen "Surat Tugas Mail Merge 2026.docx" yang diberikan sebagai referensi.
INSERT INTO document_templates
(name,letter_type_id,description,content_html,source_file_name,source_file_path,source_format,is_active,created_by,updated_by)
SELECT
    'Surat Tugas BPS - Contoh Pak Citra',
    lt.id,
    'Prototype template Surat Tugas berdasarkan contoh Word Pak Citra. Dapat diedit dari Editor Visual.',
    '<div class="bps-task-template"><div class="bps-title"><img class="bps-doc-logo" src="/assets/images/bps-logo-surat.jpg"><h2>SURAT TUGAS</h2><p>Nomor : {{NOMOR_SURAT}}</p></div><div class="bps-task-grid"><div class="label">Menimbang</div><div>:</div><div>Bahwa dalam rangka pencapaian Indikator Kinerja Utama dan penguatan tata kelola pemerintahan yang baik di lingkungan Badan Pusat Statistik, maka perlu penugasan sebagai berikut:</div><div class="label">Mengingat</div><div>:</div><div><ol><li>Undang-Undang No.16 Tahun 1997 tentang Statistik;</li><li>Peraturan Presiden Nomor 1 Tahun 2025 tentang Perubahan atas Peraturan Presiden Nomor 86 Tahun 2007 tentang Badan Pusat Statistik;</li><li>Peraturan Pemerintah No. 51 Tahun 1999 tentang Penyelenggaraan Statistik;</li><li>Keputusan Presiden RI No. 42 Tahun 2002 tentang Pedoman Pelaksanaan APBN;</li><li>Peraturan Menteri Keuangan No. 113/PMK.05/2012 tentang Perjalanan Dinas Dalam Negeri;</li><li>Perpres No. 39 Tahun 2019 tentang Satu Data Indonesia;</li><li>Peraturan Kepala Badan Pusat Statistik No. 103 tahun 2014 tentang pelaksanaan perjalanan dinas jabatan di lingkungan Badan Pusat Statistik;</li><li>Semua biaya yang timbul dengan diterbitkannya Surat Tugas ini dibebankan kepada DIPA BPS Kota Palangka Raya.</li></ol></div></div><div class="bps-command">Memberi Perintah</div><div class="bps-task-grid"><div>Nama</div><div>:</div><div>{{TUJUAN}}</div><div>Untuk</div><div>:</div><div>{{PERIHAL}} {{KETERANGAN_TUGAS}}</div><div>Pembebanan</div><div>:</div><div>{{PEMBEBANAN}}</div></div><div class="bps-signature"><p>{{TEMPAT_TTD}}, {{TANGGAL_SURAT_LONG}}</p><p>{{JABATAN_PENANDA_TANGAN}},</p><br><br><strong>{{NAMA_PENANDA_TANGAN}}</strong><p>{{NIP_PENANDA_TANGAN}}</p>{{QR_VERIFIKASI}}</div><div class="bps-integrity-footer">Dalam rangka menjaga keberlanjutan Zona Integritas Wilayah Bebas dari Korupsi (WBK) menuju Wilayah Birokrasi Bersih Melayani (WBBM),<br>BPS Kota Palangka Raya berkomitmen untuk selalu menjaga integritas dan memegang teguh nilai-nilai ASN BerAKHLAK.<br>Apabila dalam pelayanan kami terdapat penyimpangan/pelanggaran kode etik, mohon berkenan melaporkan melalui lapor.go.id atau Whatsapp SiRentas 0858-1000-6271</div><div class="page-break"></div><div class="bps-attachment-title">Lampiran Surat Tugas</div><p>Nomor : {{NOMOR_SURAT}}<br>Tanggal : {{TANGGAL_SURAT_LONG}}</p><div class="bps-signature"><p>Mengetahui,<br>{{JABATAN_PENANDA_TANGAN}},</p><br><strong>{{NAMA_PENANDA_TANGAN}}</strong></div><table class="travel-grid"><tbody><tr><td>1. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>8. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr><tr><td>2. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>9. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr><tr><td>3. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>10. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr><tr><td>4. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>11. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr><tr><td>5. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>12. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr><tr><td>6. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>13. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr><tr><td>7. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td><td>14. Pada Tanggal :<br>.........................<br><br>Tiba Di :<br>.........................</td></tr></tbody></table></div>',
    'Surat Tugas Mail Merge 2026.docx',
    'storage/template-sources/Surat Tugas Mail Merge 2026.docx',
    'DOCX',
    1,
    NULL,
    NULL
FROM letter_types lt
WHERE lt.code='SURAT_PERINTAH_TUGAS'
LIMIT 1;

SET FOREIGN_KEY_CHECKS=1;
