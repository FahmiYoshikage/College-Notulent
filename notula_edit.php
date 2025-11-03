<?php
require_once 'config.php';
requireLogin();

$notula_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Ambil data notula
$stmt = $conn->prepare("SELECT * FROM notula WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $notula_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Notula tidak ditemukan atau Anda tidak memiliki akses untuk mengedit');
    header("Location: dashboard.php");
    exit();
}

$notula = $result->fetch_assoc();

// Simpan revisi sebelum update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan riwayat revisi
    $stmt = $conn->prepare("
        INSERT INTO notula_revisi (notula_id, isi_notula_lama, status_lama, revised_by, keterangan) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $keterangan = "Revisi otomatis sebelum update";
    $stmt->bind_param("issis", $notula_id, $notula['isi_notula'], $notula['status'], $user_id, $keterangan);
    $stmt->execute();
    
    // Update notula
    $judul = sanitizeInput($_POST['judul']);
    $tanggal = sanitizeInput($_POST['tanggal']);
    $waktu = sanitizeInput($_POST['waktu']);
    $lokasi = sanitizeInput($_POST['lokasi']);
    $isi_notula = sanitizeHTML($_POST['isi_notula']);
    $daftar_peserta = sanitizeInput($_POST['daftar_peserta']);
    $status = sanitizeInput($_POST['status']);
    
    $stmt = $conn->prepare("
        UPDATE notula 
        SET judul = ?, tanggal = ?, waktu = ?, lokasi = ?, isi_notula = ?, daftar_peserta = ?, status = ?
        WHERE id = ? AND created_by = ?
    ");
    $stmt->bind_param("sssssssii", $judul, $tanggal, $waktu, $lokasi, $isi_notula, $daftar_peserta, $status, $notula_id, $user_id);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Notula berhasil diupdate!');
        header("Location: notula_detail.php?id=$notula_id");
        exit();
    } else {
        $errors[] = "Terjadi kesalahan saat update notula";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Notula - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#isi_notula',
            height: 400,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | table | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }'
        });
    </script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="card" style="max-width: 900px; margin: 40px auto;">
            <div class="card-header">
                <h2>Edit Notula</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="judul">Judul Rapat/Materi Kuliah *</label>
                        <input type="text" id="judul" name="judul" class="form-control" 
                               value="<?= htmlspecialchars($notula['judul']) ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="tanggal">Tanggal *</label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control" 
                                   value="<?= $notula['tanggal'] ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="waktu">Waktu *</label>
                            <input type="time" id="waktu" name="waktu" class="form-control" 
                                   value="<?= $notula['waktu'] ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lokasi">Lokasi</label>
                        <input type="text" id="lokasi" name="lokasi" class="form-control" 
                               value="<?= htmlspecialchars($notula['lokasi']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="daftar_peserta">Daftar Peserta</label>
                        <textarea id="daftar_peserta" name="daftar_peserta" class="form-control" rows="3"><?= htmlspecialchars($notula['daftar_peserta']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="isi_notula">Isi Notula *</label>
                        <textarea id="isi_notula" name="isi_notula"><?= $notula['isi_notula'] ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="draft" <?= $notula['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="final" <?= $notula['status'] == 'final' ? 'selected' : '' ?>>Final</option>
                            <option value="perlu_revisi" <?= $notula['status'] == 'perlu_revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Update Notula</button>
                        <a href="notula_detail.php?id=<?= $notula_id ?>" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>