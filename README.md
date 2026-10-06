# PENA MAS v3.2 - Opsi 1: Preview Visual + DOCX Presisi

Versi ini memakai **Opsi 1**: tampilan editor kembali menggunakan preview visual yang rapi seperti versi sebelumnya, sedangkan hasil DOCX final tetap menggunakan file Word asli melalui engine **DOCX Presisi**.

Perbaikan utama:
- Template contoh Pak Citra otomatis dikenali dan ditampilkan sebagai preview visual terstruktur, bukan teks/angka mentah hasil ekstraksi XML.
- File DOCX asli tidak diubah oleh preview; output presisi tetap mengambil layout Word sumber.
- Template lama yang sudah terlanjur menyimpan preview mentah akan diperbaiki otomatis saat halaman edit dibuka.
- Jika source_format sempat berubah menjadi HTML saat menyimpan template DOCX, sistem memulihkannya ke DOCX_EXACT berdasarkan file sumber `.docx`.
- Menyimpan metadata/template tanpa upload ulang tidak lagi menghilangkan mode DOCX Presisi.
- Tidak memerlukan migration database baru.

---

# PENA MAS v3.1 - DOCX Presisi Pak Citra

Versi ini menambahkan mode **DOCX Presisi**. Untuk template Word, PENA MAS tidak lagi mengandalkan hasil konversi HTML sebagai keluaran final. Sistem menyalin file DOCX sumber lalu mengisi **Mail Merge field** atau placeholder langsung di dalam paket Word sehingga logo, margin, tabel, font, header/footer, jarak, dan susunan halaman tetap berasal dari file asli.

Template contoh `Surat Tugas Mail Merge 2026.docx` sudah didukung otomatis untuk field `No_SURAT`, `Pembebanan`, `Nama_tulis_sesuai_sheet_Data_Pegawai_j`, `Keperluan`, `Tgl_Pelaksanaan`, `Tujuan`, `Mata_AnggaranPembebanan`, dan `Tgl_Surat`. Nama contoh `Amos Adam Residul` juga diganti dengan penanda tangan yang dipilih.

Pada halaman dokumen tersedia tombol **DOCX Presisi**. Untuk batch tersedia **Unduh Semua DOCX Presisi** dalam satu ZIP. Preview HTML tetap ada untuk pemeriksaan cepat, tetapi bukan acuan layout final.

## PDF

Jika server memiliki LibreOffice/soffice dan fungsi shell PHP diizinkan, PENA MAS dapat menampilkan tombol **PDF Presisi** yang mengonversi DOCX Presisi ke PDF. Path executable dapat diisi melalui `OFFICE_BIN` pada `.env`. Shared hosting seperti InfinityFree umumnya tidak menyediakan proses ini; pada kondisi tersebut gunakan DOCX Presisi lalu pilih *Save as PDF* dari Word/LibreOffice. PDF yang di-upload tetap disimpan utuh sebagai referensi visual, karena PDF final tidak aman dibangun ulang tanpa mesin PDF/koordinat field resmi.

> Catatan: kesetiaan layout berarti struktur dan formatting template asli dipertahankan. Jika nilai pengganti jauh lebih panjang dari data contoh, Word tetap dapat melakukan reflow teks secara alami di dalam area template aslinya.

---

# PENA MAS v3.0 Pak Citra - Advanced Prototype

**Penomoran Agenda dan Manajemen Arsip Surat**  
Badan Pusat Statistik Kota Palangka Raya

Versi ini dibangun di atas source PENA MAS terbaru dan memperluas v2.0 dengan seluruh kebutuhan lanjutan yang sebelumnya masih ditunda. Bagian yang belum memiliki API resmi dibuat sebagai **prototype internal yang berfungsi** agar alurnya dapat diuji lebih dahulu bersama Pak Citra.

## Fitur yang dipertahankan

- Penomoran 62710 dan 62711 terpisah.
- Batch / multiple tujuan.
- Katalog KKA.
- Agenda surat keluar dan perlindungan Rahasia/Sangat Rahasia.
- Surat masuk + workflow Baru → Diproses → Selesai.
- Laporan & Ekspor CSV/PDF.
- Riwayat Aktivitas.
- Dashboard analitik.
- Pencarian global.
- Notifikasi.
- User management.
- UI responsif desktop/mobile.

## Revisi Advanced Pak Citra

### 1. Template Dokumen + contoh Surat Tugas

Project membawa contoh sumber:

`storage/template-sources/Surat Tugas Mail Merge 2026.docx`

serta template **Surat Tugas BPS - Contoh Pak Citra** yang mengikuti struktur utama dokumen contoh:

- logo BPS,
- SURAT TUGAS dan nomor,
- Menimbang,
- Mengingat,
- Memberi Perintah,
- Nama,
- Untuk,
- Pembebanan,
- blok penanda tangan,
- lampiran perjalanan halaman berikutnya.

Placeholder baru mencakup antara lain:

- `{{NOMOR_SURAT}}`
- `{{TANGGAL_SURAT}}`
- `{{TANGGAL_SURAT_LONG}}`
- `{{TUJUAN}}`
- `{{PERIHAL}}`
- `{{PEMBEBANAN}}`
- `{{TEMPAT_TTD}}`
- `{{NAMA_PENANDA_TANGAN}}`
- `{{JABATAN_PENANDA_TANGAN}}`
- `{{NIP_PENANDA_TANGAN}}`
- `{{QR_VERIFIKASI}}`

### 2. Upload DOCX / HTML / TXT / PDF referensi

Administrator dapat mengunggah file sumber template. DOCX akan dicoba dibaca otomatis jika `ZipArchive` tersedia pada server. PDF disimpan sebagai referensi dan isi layout dapat ditata melalui Editor Visual.

### 3. Editor Visual Template

- content-editable canvas,
- placeholder dapat diklik atau drag & drop,
- mode HTML lanjutan,
- source file tetap tersimpan untuk referensi.

### 4. Master Pegawai

Akun login dan pegawai dipisahkan. Administrator dapat:

- tambah/edit/nonaktifkan pegawai,
- import CSV `name,nip,position,work_team,email`,
- memilih pegawai sebagai tujuan batch surat,
- menjadikan pegawai sebagai Penanda Tangan.

### 5. Prototype Tanda Tangan + Passkey Batch

Penanda tangan dapat diberi **Passkey Prototype**. Passkey disimpan dalam bentuk hash bcrypt, bukan plaintext.

Alur:

1. generate dokumen,
2. ajukan tanda tangan,
3. Administrator memasukkan passkey penanda tangan,
4. dokumen berubah menjadi `SIGNED`,
5. hash dan token verifikasi dibuat,
6. untuk batch, satu passkey dapat menandatangani semua dokumen yang menunggu dengan penanda tangan yang sama.

> Ini **bukan TTE tersertifikasi**. Ketika API resmi BPS/BSrE tersedia, modul prototype ini menjadi titik integrasi.

### 6. QR Verifikasi Prototype

Setiap dokumen prototype yang sudah ditandatangani memperoleh token unik dan halaman publik:

`/verify/{token}`

QR diarahkan ke URL verifikasi tersebut. Rendering gambar QR menggunakan QuickChart pada prototype, sedangkan halaman verifikasinya tetap berasal dari PENA MAS sendiri.

### 7. Lampiran Surat Masuk

Surat masuk dapat memiliki lampiran:

- PDF,
- JPG,
- PNG,
- maksimal 8 MB.

File disimpan di `storage/uploads/incoming` dan dibuka melalui route terproteksi hak akses, bukan link public langsung.

### 8. Backup & Pemulihan

Menu Administrator **Backup & Pemulihan** menyediakan:

- download backup data JSON,
- restore backup,
- verifikasi password Administrator,
- konfirmasi `PULIHKAN` sebelum proses restore.

Backup database tidak menyertakan file binary pada folder `storage/uploads`.

### 9. Panduan di dalam aplikasi

Menu **Panduan** menjelaskan alur utama untuk staf dan Administrator, termasuk penjelasan bahwa TTE/QR masih dalam mode prototype.

### 10. Light mode & UI modul baru

Komponen v3.0 memiliki aturan dark/light tambahan dan responsive mobile untuk editor template, workflow tanda tangan, pegawai, attachment, panduan, dan backup.

## Upgrade dari v2.0 / source sebelumnya

**Backup database terlebih dahulu.**

Jika database sudah menggunakan migration advanced v2.0, import satu kali:

```text
database/migrations/20261002_prototype_suite.sql
```

Jika database masih dari versi sebelum v2.0, jalankan berurutan:

```text
database/migrations/20261002_advanced_workflow_documents.sql
database/migrations/20261002_prototype_suite.sql
```

Untuk instalasi baru, cukup import:

```text
database/mysql_schema_seed.sql
```

Jangan menjalankan migration lagi setelah fresh seed.

## Uji prototype yang disarankan

1. Login Administrator.
2. Buka **Master Pegawai**, tambah/import pegawai.
3. Jadikan satu pegawai sebagai penanda tangan.
4. Buka **Penanda Tangan**, atur passkey prototype minimal 6 karakter.
5. Buka **Template Dokumen** dan cek template Surat Tugas contoh Pak Citra.
6. Generate satu nomor `Surat Perintah/Tugas Daerah`.
7. Buka **Buat Dokumen**, pilih template contoh dan penanda tangan.
8. Isi Pembebanan dan buat preview.
9. Klik **Ajukan Tanda Tangan**.
10. Masukkan passkey prototype dan tanda tangani.
11. Pastikan QR/verifikasi muncul dan `/verify/{token}` dapat dibuka.
12. Ulangi dengan beberapa surat melalui **Dokumen & Cetak** dan uji **Tanda Tangani Semua** menggunakan satu passkey.
13. Catat Surat Masuk dengan lampiran PDF/JPG/PNG.
14. Uji Backup & Pemulihan pada database pengujian.
15. Uji dark/light mode dan mobile.

## Catatan keamanan

- `.env` tidak perlu dibagikan ke pihak lain.
- Passkey prototype tidak disimpan plaintext.
- Password akun tidak masuk audit log.
- Surat Rahasia/Sangat Rahasia tetap memakai aturan akses yang sudah ada.
- Jangan menganggap QR prototype sebagai TTE resmi.
- Untuk deployment kantor, integrasi TTE resmi harus mengikuti dokumentasi dan kebijakan BPS/BSrE.
