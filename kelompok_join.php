<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_kelompok = strtoupper(sanitizeInput($_POST['kode_kelompok']));
    $user_id = $_SESSION['user_id'];
    
    $errors = [];
    
    if (empty($kode_kelompok)) {
        $errors[] = "Kode kelompok harus diisi";
    } else {
        $conn = getConnection();
        
        // Cari kelompok berdasarkan kode
        $stmt = $conn->prepare("SELECT id, nama_kelompok FROM kelompok WHERE kode_kelompok = ?");
        $stmt->bind_param("s", $kode_kelompok);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "Kelompok dengan kode tersebut tidak ditemukan";
        } else {
            $kelompok = $result->fetch_assoc();
            $kelompok_id = $kelompok['id'];
            
            // Cek apakah sudah bergabung
            $stmt = $conn->prepare("SELECT id FROM user_kelompok WHERE user_id = ? AND kelompok_id = ?");
            $stmt->bind_param("ii", $user_id, $kelompok_id);
            $stmt->execute();
            $check = $stmt->get_result();
            
            if ($check->num_rows > 0) {
                $errors[] = "Anda sudah bergabung di kelompok ini";
            } else {
                // Gabung ke kelompok
                $stmt = $conn->prepare("INSERT INTO user_kelompok (user_id, kelompok_id, role_kelompok) VALUES (?, ?, 'anggota')");
                $stmt->bind_param("ii", $user_id, $kelompok_id);
                
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Berhasil bergabung ke kelompok ' . $kelompok['nama_kelompok']);
                    header("Location: kelompok_detail.php?id=$kelompok_id");
                    exit();
                } else {
                    $errors[] = "Terjadi kesalahan saat bergabung";
                }
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gabung Kelompok - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 40px auto;">
            <div class="card-header">
                <h2>Gabung Kelompok</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="kode_kelompok">Kode Kelompok</label>
                        <input type="text" id="kode_kelompok" name="kode_kelompok" class="form-control" 
                               placeholder="Masukkan kode kelompok" required>
                        <small class="text-muted">Minta kode kelompok dari ketua atau anggota kelompok</small>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Gabung</button>
                        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>