# Platform Notula Rapat/Kuliah Interaktif

Platform berbasis PHP Native untuk membuat, mengelola, dan mengekspor notula rapat atau kuliah dengan fitur Rich Text Editor dan konversi PDF.

## 🚀 Fitur Utama

### ✅ CRUD Dasar

-   **Autentikasi**: Login & Registrasi dengan role (Mahasiswa/Dosen/Admin)
-   **Manajemen Kelompok**: Buat dan bergabung ke kelompok/mata kuliah
-   **Manajemen Notula**: Create, Read, Update, Delete notula

### 🎯 Fitur Revolusioner

-   **Rich Text Editor**: TinyMCE untuk menulis notula dengan format lengkap
-   **Ekspor ke PDF**: Konversi notula ke dokumen PDF profesional menggunakan Dompdf
-   **Pencarian Lanjutan**: Cari notula berdasarkan judul, isi, atau peserta
-   **Riwayat Revisi**: Simpan perubahan notula secara otomatis
-   **Hak Akses**: Kontrol siapa yang bisa melihat notula

## 📋 Persyaratan Sistem

-   PHP 7.4 atau lebih tinggi
-   MySQL 5.7 atau lebih tinggi
-   Apache/Nginx Web Server
-   Composer (opsional, untuk Dompdf)

## 🛠️ Instalasi

### 1. Clone atau Download Project

```bash
git clone <repository-url>
cd notula_platform
```

### 2. Buat Database

```sql
CREATE DATABASE notula_platform;
```

Import file SQL:

```bash
mysql -u root -p notula_platform < notula_database.sql
```

Atau jalankan query SQL dari file `notula_database.sql` di phpMyAdmin.

### 3. Konfigurasi Database

Edit file `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan password MySQL Anda
define('DB_NAME', 'notula_platform');
```

### 4. Install Dompdf

#### Opsi A: Menggunakan Composer (Recommended)

```bash
composer require dompdf/dompdf
```

#### Opsi B: Manual Download

1. Download Dompdf dari: https://github.com/dompdf/dompdf/releases
2. Ekstrak ke folder `dompdf/` di root project
3. Pastikan struktur folder seperti ini:
    ```
    notula_platform/
    ├── dompdf/
    │   ├── autoload.inc.php
    │   ├── src/
    │   └── ...
    ├── config.php
    ├── login.php
    └── ...
    ```

### 5. Setup Permissions (Linux/Mac)

```bash
chmod -R 755 notula_platform/
chmod -R 777 dompdf/lib/fonts/
```

### 6. Docker (optional)

If you prefer running the app in containers, a simple Docker setup is included.

-   Build and start the app and database:

```bash
docker-compose up -d --build
```

-   The web app will be available at: `http://localhost:8080`

-   Database connection details for `config.php` when using Docker Compose (default in `docker-compose.yml`):

```php
define('DB_HOST', 'db');
define('DB_USER', 'notula');
define('DB_PASS', 'notulapass');
define('DB_NAME', 'notula_platform');
```

-   The MySQL service will automatically import `notula_database.sql` on the first run because the SQL file is mounted into MySQL's init directory. If you change the SQL file after the initial run, remove the `db_data` volume to reinitialize (WARNING: this will delete existing DB data):

```bash
docker-compose down
docker volume rm project_wtwa_db_data
docker-compose up -d --build
```

-   To view container logs:

```bash
docker-compose logs -f web
docker-compose logs -f db
```

Notes:

-   `DB_HOST` must be `db` because the database runs in a separate container in the same Docker network.
-   If you prefer a different MySQL password or DB name, update `docker-compose.yml` and `config.php` accordingly.

### 7. Setup TinyMCE API Key

TinyMCE rich text editor memerlukan API key. Untuk mendapatkan dan mengkonfigurasinya:

1. Kunjungi https://www.tiny.cloud/auth/signup/ dan daftar gratis
2. Copy API key dari dashboard
3. Copy file `.env.example` menjadi `.env`: `cp .env.example .env`
4. Edit file `.env` dan ganti `YOUR_TINYMCE_API_KEY` dengan API key Anda
5. Restart container: `docker-compose down && docker-compose up -d`

**Penting:** File `.env` tidak akan di-commit ke git (sudah ada di `.gitignore`), jadi API key Anda aman!

Lihat `TINYMCE_SETUP.md` untuk detail lengkap.

### 8. Akses Aplikasi

Buka browser dan akses:

```
http://localhost/notula_platform/login.php
```

## 👤 Akun Default

Setelah instalasi, Anda bisa login dengan:

**Admin:**

-   Email: `admin@notula.com`
-   Password: `password`

**Dosen:**

-   Email: `budi@univ.ac.id`
-   Password: `password`

**Mahasiswa:**

-   Email: `andi@student.ac.id`
-   Password: `password`

## 📁 Struktur File

```
notula_platform/
├── config.php                 # Konfigurasi database dan helper
├── auth.php                   # Proses login & registrasi
├── login.php                  # Halaman login
├── register.php               # Halaman registrasi
├── dashboard.php              # Dashboard utama
├── header.php                 # Header navigation
├── style.css                  # Global stylesheet
│
├── kelompok_create.php        # Buat kelompok baru
├── kelompok_join.php          # Gabung ke kelompok
├── kelompok_list.php          # Daftar kelompok
├── kelompok_detail.php        # Detail kelompok
│
├── notula_create.php          # Buat notula baru (dengan Rich Text Editor)
├── notula_list.php            # Daftar semua notula
├── notula_detail.php          # Detail notula
├── notula_edit.php            # Edit notula
├── notula_delete.php          # Hapus notula
├── notula_export.php          # Ekspor notula ke PDF (Dompdf)
├── notula_search.php          # Pencarian notula
│
└── dompdf/                    # Library Dompdf untuk konversi PDF
```

## 🎨 Alur Penggunaan

### 1. Registrasi & Login

-   Daftar akun baru dengan memilih role (Mahasiswa/Dosen)
-   Login menggunakan email dan password

### 2. Buat atau Gabung Kelompok

-   **Buat Kelompok Baru**: Beri nama, deskripsi, dan kode kelompok unik
-   **Gabung Kelompok**: Masukkan kode kelompok untuk bergabung

### 3. Buat Notula

-   Pilih kelompok/mata kuliah
-   Isi metadata: judul, tanggal, waktu, lokasi, peserta
-   Tulis isi notula menggunakan Rich Text Editor (TinyMCE)
-   Pilih status: Draft, Final, atau Perlu Revisi
-   Simpan notula

### 4. Kelola Notula

-   Lihat detail notula
-   Edit notula (jika Anda pembuat)
-   Hapus notula (jika Anda pembuat)
-   Cari notula dengan kata kunci

### 5. Ekspor ke PDF

-   Buka detail notula
-   Klik tombol "Ekspor ke PDF"
-   File PDF akan otomatis terunduh

## 🔒 Keamanan

### Fitur Keamanan yang Diterapkan:

-   **Password Hashing**: Menggunakan `password_hash()` PHP
-   **Input Sanitization**: Semua input dibersihkan dengan `sanitizeInput()`
-   **HTML Sanitization**: Konten Rich Text Editor dibersihkan dari script berbahaya
-   **Prepared Statements**: Mencegah SQL Injection
-   **Session Management**: Login session dengan timeout otomatis
-   **CSRF Protection**: Token untuk form submission (bisa ditambahkan)

## 🧪 Testing

### Test Case yang Bisa Dicoba:

1. **Autentikasi**

    - Login dengan kredensial valid/invalid
    - Registrasi dengan email duplikat
    - Logout

2. **Kelompok**

    - Buat kelompok dengan kode unik
    - Gabung kelompok dengan kode valid/invalid
    - Lihat anggota kelompok

3. **Notula**

    - Buat notula dengan Rich Text formatting
    - Edit notula
    - Hapus notula
    - Ekspor notula ke PDF

4. **Pencarian**
    - Cari berdasarkan judul
    - Cari berdasarkan isi notula
    - Cari berdasarkan nama peserta

## 🐛 Troubleshooting

### PDF Export Tidak Berfungsi

**Error: "Class 'Dompdf\Dompdf' not found"**

-   Pastikan Dompdf sudah terinstall
-   Periksa path di `notula_export.php`

**Error: "Permission denied"**

```bash
chmod -R 777 dompdf/lib/fonts/
```

### Rich Text Editor Tidak Muncul

**TinyMCE tidak load:**

-   Pastikan koneksi internet aktif (TinyMCE load dari CDN)
-   Atau download TinyMCE dan simpan secara lokal

### Database Connection Error

**Error: "Connection failed"**

-   Periksa konfigurasi di `config.php`
-   Pastikan MySQL service berjalan
-   Cek username dan password MySQL

## 📚 Teknologi yang Digunakan

-   **Backend**: PHP Native (tanpa framework)
-   **Database**: MySQL dengan MySQLi
-   **Frontend**: HTML5, CSS3, Vanilla JavaScript
-   **Rich Text Editor**: TinyMCE 6
-   **PDF Generator**: Dompdf
-   **Authentication**: PHP Session & Cookie

## 🎯 Workflow Teknis

1. **User Input** → Form HTML
2. **Data Processing** → PHP Native (sanitasi & validasi)
3. **Data Storage** → MySQL (prepared statements)
4. **Rich Text** → TinyMCE → HTML sanitized → Simpan ke DB
5. **PDF Export** → Ambil data → Format template HTML → Dompdf → Generate PDF → Download

## 📝 Catatan Penting

### Password Default

Semua akun default menggunakan password: `password`

Hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

### Base URL

Sesuaikan `BASE_URL` di `config.php` dengan lokasi project Anda:

```php
define('BASE_URL', 'http://localhost/notula_platform/');
```

### TinyMCE API Key

Untuk production, daftarkan API key gratis di: https://www.tiny.cloud/

## 🚀 Pengembangan Lanjutan

Fitur yang bisa ditambahkan:

-   Real-time collaboration dengan WebSocket
-   Notifikasi email untuk notula baru
-   Upload file attachment (PDF, Word, Image)
-   Export ke format lain (Word, Excel)
-   Integrasi dengan Google Drive/Dropbox
-   Mobile app (Progressive Web App)
-   API REST untuk integrasi eksternal

## 📄 Lisensi

Project ini dibuat untuk keperluan pembelajaran dan dapat dimodifikasi sesuai kebutuhan.

## 💡 Kontributor

Dibuat berdasarkan workflow yang komprehensif untuk pembelajaran Web Programming dengan PHP Native.

---

**Happy Coding! 🎉**
