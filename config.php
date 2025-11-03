<?php
// config.php - Konfigurasi Database dan Session

session_start();

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
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fungsi untuk sanitasi HTML dari Rich Text Editor
function sanitizeHTML($html) {
    // Daftar tag yang diizinkan
    $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><table><tr><td><th><thead><tbody><a><img><blockquote><code><pre>';
    
    $html = strip_tags($html, $allowed_tags);
    
    // Bersihkan atribut berbahaya
    $html = preg_replace('/(<[^>]+) on\w+="[^"]*"/i', '$1', $html);
    $html = preg_replace('/(<[^>]+) style="[^"]*"/i', '$1', $html);
    
    return $html;
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