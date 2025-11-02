<?php
require_once 'config.php';
requireLogin();

$notula_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Cek apakah notula milik user
$stmt = $conn->prepare("SELECT id FROM notula WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $notula_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Notula tidak ditemukan atau Anda tidak memiliki akses untuk menghapus');
    header("Location: dashboard.php");
    exit();
}

// Hapus notula
$stmt = $conn->prepare("DELETE FROM notula WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $notula_id, $user_id);

if ($stmt->execute()) {
    setFlashMessage('success', 'Notula berhasil dihapus!');
} else {
    setFlashMessage('error', 'Terjadi kesalahan saat menghapus notula');
}

$stmt->close();
$conn->close();

header("Location: notula_list.php");
exit();
?>