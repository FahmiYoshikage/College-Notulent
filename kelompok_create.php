<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelompok = sanitizeInput($_POST['nama_kelompok']);
    $deskripsi = sanitizeInput($_POST['deskripsi']);
    $kode_kelompok = strtoupper(sanitizeInput($_POST['kode_kelompok']));
    $user_id = $_SESSION['user_id'];
    
    $errors = [];
    
    if (empty($nama_kelompok)) $errors[] = "Nama kelompok harus diisi";
    if (empty($kode_kelompok)) $errors[] = "Kode kelompok harus diisi";
    if (strlen($kode_kelompok) < 4) $errors[] = "Kode kelompok minimal 4 karakter";
    
    if (empty($errors)) {
        $conn = getConnection();
        
        // Cek kode kelompok sudah ada
        $stmt = $conn->prepare("SELECT id FROM kelompok WHERE kode_kelompok = ?");
        $stmt->bind_param("s", $kode_kelompok);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Kode kelompok sudah digunakan";
        } else {
            // Insert kelompok baru
            $stmt = $conn->prepare("INSERT INTO kelompok (nama_kelompok, deskripsi, kode_kelompok, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $nama_kelompok, $deskripsi, $kode_kelompok, $user_id);
            
            if ($stmt->execute()) {
                $kelompok_id = $conn->insert_id;
                
                // Tambahkan creator sebagai ketua kelompok
                $stmt = $conn->prepare("INSERT INTO user_kelompok (user_id, kelompok_id, role_kelompok) VALUES (?, ?, 'ketua')");
                $stmt->bind_param("ii", $user_id, $kelompok_id);
                $stmt->execute();
                
                setFlashMessage('success', 'Kelompok berhasil dibuat!');
                header("Location: kelompok_detail.php?id=$kelompok_id");
                exit();
            } else {
                $errors[] = "Terjadi kesalahan saat membuat kelompok";
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
    <title>Buat Kelompok - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 40px auto;">
            <div class="card-header">
                <h2>Buat Kelompok Baru</h2>
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
                        <label for="nama_kelompok">Nama Kelompok/Mata Kuliah *</label>
                        <input type="text" id="nama_kelompok" name="nama_kelompok" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="kode_kelompok">Kode Kelompok *</label>
                        <input type="text" id="kode_kelompok" name="kode_kelompok" class="form-control" 
                               placeholder="Contoh: WEBPRO2025" required>
                        <small class="text-muted">Minimal 4 karakter, akan digunakan untuk bergabung ke kelompok</small>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Buat Kelompok</button>
                        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>