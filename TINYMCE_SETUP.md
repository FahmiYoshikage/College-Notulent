# Setup TinyMCE API Key

TinyMCE memerlukan API key dan domain registration untuk menggunakan CDN mereka. Berikut langkah-langkahnya:

## 1. Dapatkan API Key Gratis

1. Kunjungi: https://www.tiny.cloud/auth/signup/
2. Daftar akun gratis (tidak perlu kartu kredit)
3. Setelah login, Anda akan mendapatkan API key di dashboard
4. **PENTING:** Klik "Approved Domains" dan tambahkan domain Anda:
    - Untuk local: `localhost`
    - Untuk production: `notulent.fahmi.app`
    - Atau gunakan wildcard: `*.fahmi.app`
5. Copy API key tersebut

## 2. Konfigurasi API Key

### Untuk Docker (Recommended - Aman untuk Git):

1. Copy file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

2. Edit file `.env` dan ganti `YOUR_TINYMCE_API_KEY` dengan API key Anda:

```bash
# .env file (file ini TIDAK akan di-commit ke git)
TINYMCE_API_KEY=your-actual-api-key-here
```

3. Restart container:

```bash
docker-compose down
docker-compose up -d
```

**Penting:** File `.env` sudah ada di `.gitignore` sehingga API key Anda AMAN dan tidak akan ter-commit ke GitHub!

### Untuk Local Development (tanpa Docker):

Edit file `config.php`, ganti `YOUR_TINYMCE_API_KEY` dengan API key Anda:

```php
define('TINYMCE_API_KEY', 'your-actual-api-key-here');
```

## 3. Verifikasi

Setelah mengatur API key:

1. Buka halaman "Buat Notula"
2. Rich text editor seharusnya muncul tanpa warning
3. Anda bisa mulai menulis dengan formatting lengkap

## Alternative: Gunakan Tanpa API Key

Jika tidak ingin mendaftar API key, Anda bisa menggunakan TinyMCE self-hosted:

1. Download TinyMCE: https://www.tiny.cloud/get-tiny/self-hosted/
2. Ekstrak ke folder `js/tinymce/` di project
3. Edit `notula_create.php` dan `notula_edit.php`, ubah:
    ```html
    <script src="js/tinymce/tinymce.min.js"></script>
    ```

Namun menggunakan CDN dengan API key lebih direkomendasikan karena:

-   Selalu mendapat update terbaru
-   Loading lebih cepat (CDN global)
-   Tidak perlu download file besar

## Keamanan

### ✅ AMAN - Menggunakan `.env` file (Recommended)

File `.env` sudah otomatis di-ignore oleh git melalui `.gitignore`, sehingga:

-   ✅ API key Anda **TIDAK akan ter-commit** ke GitHub
-   ✅ Setiap developer bisa punya API key sendiri
-   ✅ API key production berbeda dengan development
-   ✅ Tidak ada sensitive data di repository

### ⚠️ TIDAK AMAN - Hardcode di file PHP/YAML

**JANGAN** hardcode API key langsung di:

-   ❌ `config.php`
-   ❌ `docker-compose.yml`
-   ❌ File PHP lainnya

Karena file-file ini akan ter-commit ke git dan API key Anda akan **TEREXPOSE** ke publik!

### Deployment ke Server/VPS

Saat deploy ke server:

1. Copy `.env.example` ke `.env` di server:

    ```bash
    cp .env.example .env
    ```

2. Edit `.env` dengan credentials production:

    ```bash
    nano .env
    # atau
    vim .env
    ```

3. Set file permissions (optional tapi recommended):

    ```bash
    chmod 600 .env
    ```

4. Start container:
    ```bash
    docker-compose up -d
    ```
