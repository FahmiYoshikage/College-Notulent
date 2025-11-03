<?php
// config.php - Konfigurasi Database dan Session

// Konfigurasi session yang lebih aman
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Regenerate session ID secara berkala untuk mencegah session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Konfigurasi Database
// For Docker: use 'db' as host, for local: use 'localhost'
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', getenv('DB_USER') ?: 'notula');
define('DB_PASS', getenv('DB_PASS') ?: 'notulapass');
define('DB_NAME', getenv('DB_NAME') ?: 'notula_platform');

// TinyMCE API Key Configuration
// Get your free API key from: https://www.tiny.cloud/auth/signup/
// Set the API key via environment variable or replace 'YOUR_TINYMCE_API_KEY' below
define('TINYMCE_API_KEY', getenv('TINYMCE_API_KEY') ?: 'YOUR_TINYMCE_API_KEY');

// Koneksi Database
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Fungsi Helper untuk Keamanan
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fungsi untuk sanitasi HTML dari Rich Text Editor (mencegah XSS)
function sanitizeHTML($html) {
    // Daftar tag yang diizinkan
    $allowed_tags = '<p><br><strong><em><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><table><tr><td><th><thead><tbody><tfoot><a><img><blockquote><code><pre><hr><div><span>';
    
    // Strip tags yang tidak diizinkan
    $html = strip_tags($html, $allowed_tags);
    
    // Bersihkan event handlers berbahaya (XSS protection)
    $html = preg_replace('/(<[^>]+) on\w+\s*=\s*["\'][^"\']*["\']/i', '$1', $html);
    
    // Bersihkan javascript: protocol dalam href dan src
    $html = preg_replace('/(<[^>]+(?:href|src)\s*=\s*["\'])javascript:[^"\']*(["\']\s*>)/i', '$1#$2', $html);
    
    // Bersihkan data: protocol (bisa digunakan untuk XSS)
    $html = preg_replace('/(<[^>]+(?:href|src)\s*=\s*["\'])data:[^"\']*(["\']\s*>)/i', '$1#$2', $html);
    
    // Batasi style attribute untuk mencegah CSS injection
    $html = preg_replace('/(<[^>]+) style\s*=\s*["\'][^"\']*["\']/i', '$1', $html);
    
    return $html;
}

// Fungsi untuk validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fungsi untuk generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Cek role user
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi untuk flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

// Base URL
define('BASE_URL', 'http://localhost/notula_platform/');
?>