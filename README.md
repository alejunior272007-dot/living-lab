# Living Lab Support — Dashboard Monitoring & Jurnal Limbah Organik

Aplikasi PHP 8+ dan MySQL untuk pencatatan audit limbah organik harian berbasis 5W1H.

## Fitur
- Dashboard statistik total berat, jumlah audit, dan jumlah dokumentasi.
- Input audit 5W1H.
- Rincian beberapa jenis limbah dalam satu audit.
- Kalkulasi total berat otomatis di browser dan dihitung ulang di server.
- Upload foto JPG/PNG/WEBP maksimal 5 MB per file.
- Penamaan foto otomatis: `P01_FOTO_S04_YYYYMMDD_001.jpg`.
- Preview foto sebelum dikirim.
- Jurnal dengan filter kategori dan rentang tanggal.
- Ekspor CSV yang dapat dibuka di Microsoft Excel.

## Persyaratan
- PHP 8.0 atau lebih baru.
- MySQL 5.7+ atau MariaDB 10.4+.
- Ekstensi PHP: PDO, pdo_mysql, fileinfo, dan GD (untuk validasi gambar melalui getimagesize).
- HTTPS sangat disarankan untuk hosting produksi.

## Instalasi di hosting
1. Buat database MySQL melalui panel hosting (mis. cPanel → MySQL Databases).
2. Buat user database, tetapkan password kuat, lalu berikan seluruh privilege ke database.
3. Import `database/living_lab.sql` melalui phpMyAdmin. Jika hosting tidak mengizinkan perintah CREATE DATABASE, buat database lebih dulu lalu hapus dua baris `CREATE DATABASE...` dan `USE living_lab;` dari file SQL sebelum import.
4. Edit `config/database.php`:
   - `$dbHost` sesuai host database dari penyedia hosting.
   - `$dbName` nama database.
   - `$dbUser` username database.
   - `$dbPass` password database.
5. Upload seluruh isi folder proyek ke `public_html` atau folder domain/subdomain.
6. Pastikan folder `uploads/dokumentasi` dapat ditulis oleh PHP. Umumnya permission folder `755`; bila gagal, ikuti nilai permission yang direkomendasikan hosting (hindari `777`).
7. Buka domain Anda. Dashboard akan tampil.

### Jika dipasang dalam subfolder
Misalnya URL `https://domainanda.com/living-lab/`, isi `BASE_URL` di `config/config.php` menjadi `/living-lab`. Jika dipasang langsung di document root, biarkan kosong.

## Uji fungsi
1. Buka **Input Audit Baru**.
2. Isi kode proyek `P01`, sesi `S04`, kode audit unik, tanggal, kategori, petugas, lokasi, dan deskripsi 5W1H.
3. Tambahkan dua atau lebih jenis limbah dan masukkan berat. Total harus berubah otomatis.
4. Pilih beberapa foto. Periksa preview dan nama yang diusulkan.
5. Simpan. Periksa halaman detail, jurnal, filter kategori, dan ekspor CSV.
6. Buka CSV dengan Excel dan pastikan karakter Indonesia terbaca.

## Catatan keamanan dan pengembangan
- Aplikasi ini adalah fondasi MVP dan belum memiliki login/role pengguna. Untuk penggunaan publik/organisasi, tambahkan autentikasi, otorisasi, pembatasan akses, audit trail, backup terjadwal, serta kebijakan retensi foto.
- Gunakan HTTPS. Jangan membagikan kredensial database.
- Buat kode audit unik. Database menolak kode audit duplikat.
- Foto disimpan di `uploads/dokumentasi`; file dibatasi MIME dan diperiksa sebagai gambar.
- Ekspor saat ini berupa CSV UTF-8 BOM (kompatibel dengan Excel), bukan file `.xlsx` native.
- Untuk file foto yang sangat besar atau banyak, sesuaikan `upload_max_filesize`, `post_max_size`, dan `max_file_uploads` pada konfigurasi PHP hosting.
- Pertimbangkan menyimpan upload di luar web root atau menggunakan aturan akses server tambahan bila dokumentasi bersifat sensitif.

## Struktur
```text
living-lab-support/
├── audit/                 # input, simpan, detail
├── assets/css/             # stylesheet
├── assets/js/              # kalkulasi dan preview
├── config/                 # konfigurasi dan koneksi DB
├── database/               # SQL schema
├── includes/               # helper dan layout
├── jurnal/                 # jurnal dan ekspor CSV
└── uploads/dokumentasi/    # foto audit
```
Update fitur baru dari branch fitur-test
Update fitur baru dari branch fitur-test
