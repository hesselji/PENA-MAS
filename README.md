# PENA MAS

**Penomoran Agenda dan Manajemen Arsip Surat**  
Badan Pusat Statistik Kota Palangka Raya

PENA MAS membantu penomoran surat keluar, agenda surat keluar dan masuk, klasifikasi arsip, pengelolaan pengguna, serta pembatasan akses surat rahasia dalam satu aplikasi.

## Fitur Utama

- Penomoran surat otomatis untuk jalur 62710 dan 62711.
- Pembuatan banyak nomor sekaligus untuk beberapa tujuan.
- Pencarian Kode Klasifikasi Arsip berdasarkan kode atau kata kunci.
- Agenda surat keluar bersama dengan perlindungan surat Rahasia dan Sangat Rahasia.
- Agenda surat masuk.
- Dashboard sesuai hak akses pengguna.
- Pengelolaan pengguna dan pengaturan ulang password oleh Administrator.
- Tampilan responsif untuk komputer dan perangkat seluler.

## Persiapan Produksi

1. Salin `.env.production.example` menjadi `.env` pada server tujuan.
2. Isi alamat aplikasi dan koneksi penyimpanan data sesuai lingkungan server.
3. Siapkan struktur data menggunakan `database/mysql_schema_seed.sql` untuk instalasi baru.
4. Untuk instalasi baru tanpa akun, buat Administrator pertama melalui terminal:

   ```bash
   php tools/create_admin.php
   ```

5. Pastikan `APP_ENV=production` dan `APP_DEBUG=false` sebelum aplikasi digunakan.

## Keamanan

- File `.env` tidak disertakan dalam paket dan tidak boleh dibagikan.
- Password pengguna disimpan menggunakan hash aman.
- Surat Rahasia dan Sangat Rahasia hanya dapat dibuat dan dilihat Administrator.
- Pembukaan detail surat rahasia membutuhkan verifikasi ulang password.
- Aksi penting dicatat pada riwayat sistem tanpa menyimpan password.

## Catatan Penyerahan

Paket ini tidak menyertakan akun contoh atau kredensial contoh. Gunakan akun resmi yang dibuat oleh pengelola aplikasi dan sesuaikan data awal sebelum digunakan untuk pekerjaan kantor.
