# Dokumentasi Keamanan

Platform Notula telah dilengkapi dengan berbagai fitur keamanan untuk melindungi dari serangan umum.

## 🛡️ Fitur Keamanan yang Diimplementasikan

### 1. **SQL Injection Protection**

**Implementasi:**

-   ✅ Menggunakan **Prepared Statements** di semua query database
-   ✅ Parameter binding dengan `bind_param()`
-   ✅ Validasi tipe data (integer, string, dll)

**Contoh:**

```php
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
```

**Mencegah:**

-   SQL injection melalui input form
-   Union-based attacks
-   Blind SQL injection

---

### 2. **Cross-Site Scripting (XSS) Protection**

**Implementasi:**

-   ✅ `htmlspecialchars()` untuk semua output user
-   ✅ `sanitizeHTML()` untuk rich text content
-   ✅ Whitelist tag HTML yang diizinkan
-   ✅ Remove event handlers (`onclick`, `onload`, dll)
-   ✅ Block `javascript:` dan `data:` protocol dalam link

**Fungsi Keamanan:**

```php
function sanitizeHTML($html) {
    // Strip tags berbahaya
    $html = strip_tags($html, $allowed_tags);

    // Remove event handlers
    $html = preg_replace('/(<[^>]+) on\w+\s*=\s*["\'][^"\']*["\']/i', '$1', $html);

    // Block dangerous protocols
    $html = preg_replace('/(<[^>]+(?:href|src)\s*=\s*["\'])javascript:[^"\']*(["\']\s*>)/i', '$1#$2', $html);

    return $html;
}
```

**Mencegah:**

-   Stored XSS (melalui database)
-   Reflected XSS (melalui URL parameters)
-   DOM-based XSS

---

### 3. **Cross-Site Request Forgery (CSRF) Protection**

**Implementasi:**

-   ✅ CSRF token di semua form
-   ✅ Token validation di server-side
-   ✅ Token regeneration setelah login

**Contoh:**

```php
// Generate token
<input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

// Validate token
if (!validateCSRFToken($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

**Mencegah:**

-   Unauthorized actions
-   Form hijacking
-   One-click attacks

---

### 4. **Session Security**

**Implementasi:**

-   ✅ `httponly` cookie flag (mencegah JavaScript access)
-   ✅ `SameSite=Strict` (mencegah CSRF)
-   ✅ Session regeneration setelah login
-   ✅ Session timeout (30 menit)
-   ✅ Session fingerprinting (IP + User Agent)

**Konfigurasi:**

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Regenerate session ID
if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
}
```

**Mencegah:**

-   Session hijacking
-   Session fixation
-   Session sidejacking

---

### 5. **Password Security**

**Implementasi:**

-   ✅ `password_hash()` dengan bcrypt algorithm
-   ✅ Minimum 6 karakter
-   ✅ Password confirmation
-   ✅ Never store plain text passwords

**Contoh:**

```php
// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Verify password
if (password_verify($password, $hashed)) {
    // Login success
}
```

---

### 6. **Input Validation & Sanitization**

**Implementasi:**

-   ✅ Server-side validation untuk semua input
-   ✅ Type validation (email, integer, date, dll)
-   ✅ Whitelist validation (role, status)
-   ✅ Length validation
-   ✅ Format validation (email, date)

**Contoh:**

```php
// Email validation
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

// Integer validation
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

// Whitelist validation
if (!in_array($status, ['draft', 'final', 'perlu_revisi'])) {
    $errors[] = "Status tidak valid";
}
```

---

### 7. **Auto-Save Draft Feature**

**Implementasi:**

-   ✅ LocalStorage untuk auto-save di client
-   ✅ Session storage di server
-   ✅ Auto-save setiap 2 detik (debounced)
-   ✅ Draft restoration dengan konfirmasi
-   ✅ Draft expiration (24 jam)

**Keuntungan:**

-   Mencegah kehilangan data saat refresh
-   Backup otomatis saat menulis
-   Multi-layer storage (client + server)

---

## 🔒 Best Practices yang Diterapkan

1. **Least Privilege Principle**

    - User hanya bisa edit/delete notula sendiri
    - Role-based access control

2. **Defense in Depth**

    - Multiple layers of security
    - Client-side + server-side validation

3. **Fail Securely**

    - Default deny
    - Proper error handling tanpa leak info

4. **Keep it Simple**
    - Minimal attack surface
    - Clear security boundaries

---

## ⚠️ Rekomendasi Tambahan untuk Production

### 1. HTTPS

```apache
# Enable HTTPS di production
<VirtualHost *:443>
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
</VirtualHost>
```

### 2. Security Headers

```php
// Tambahkan di header.php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdn.tiny.cloud; style-src 'self' 'unsafe-inline';");
```

### 3. Rate Limiting

```php
// Login rate limiting (perlu implementasi)
// Max 5 login attempts per 15 minutes
```

### 4. File Upload Security (jika ditambahkan)

```php
// Validate file type
$allowed = ['jpg', 'jpeg', 'png', 'pdf'];
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($ext), $allowed)) {
    die('File type not allowed');
}

// Store outside webroot
move_uploaded_file($_FILES['file']['tmp_name'], '/secure/path/');
```

### 5. Database Security

```sql
-- Use separate database user with limited privileges
CREATE USER 'notula_app'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON notula_platform.* TO 'notula_app'@'localhost';
FLUSH PRIVILEGES;
```

---

## 🧪 Testing Security

### XSS Test Cases:

```javascript
// Try inject these in input fields:
<script>alert('XSS')</script>
<img src=x onerror=alert('XSS')>
<a href="javascript:alert('XSS')">click</a>
```

### SQL Injection Test Cases:

```sql
-- Try in email/username field:
' OR '1'='1
admin'--
' UNION SELECT NULL--
```

### CSRF Test:

-   Try submit form without token
-   Try reuse old token
-   Try token from different session

---

## 📚 Referensi

-   [OWASP Top 10](https://owasp.org/www-project-top-ten/)
-   [PHP Security Guide](https://www.php.net/manual/en/security.php)
-   [Session Security](https://www.php.net/manual/en/session.security.php)
-   [Input Validation](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)

---

**Last Updated:** November 3, 2025
