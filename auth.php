<?php
// auth.php - Proses Login dan Registrasi
require_once 'config.php';

// Proses Registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nama = sanitizeInput($_POST['nama']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitizeInput($_POST['role']);
    
    $errors = [];
    
    // Validasi
    if (empty($nama)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (empty($password)) $errors[] = "Password harus diisi";
    if ($password !== $confirm_password) $errors[] = "Password tidak cocok";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    
    if (empty($errors)) {
        $conn = getConnection();
        
        // Cek email sudah terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email sudah terdaftar";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Registrasi berhasil! Silakan login.');
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Terjadi kesalahan saat registrasi";
            }
        }
        
        $stmt->close();
        $conn->close();
    }
    
    $_SESSION['register_errors'] = $errors;
    header("Location: register.php");
    exit();
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email)) $errors[] = "Email harus diisi";
    if (empty($password)) $errors[] = "Password harus diisi";
    
    if (empty($errors)) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                setFlashMessage('success', 'Login berhasil! Selamat datang, ' . $user['nama']);
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Email atau password salah";
            }
        } else {
            $errors[] = "Email atau password salah";
        }
        
        $stmt->close();
        $conn->close();
    }
    
    $_SESSION['login_errors'] = $errors;
    header("Location: login.php");
    exit();
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>