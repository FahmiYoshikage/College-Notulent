<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Ambil kelompok yang diikuti user
$stmt = $conn->prepare("
    SELECT k.id, k.nama_kelompok 
    FROM kelompok k
    JOIN user_kelompok uk ON k.id = uk.kelompok_id
    WHERE uk.user_id = ?
    ORDER BY k.nama_kelompok
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kelompok_list = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelompok_id = (int)$_POST['kelompok_id'];
    $judul = sanitizeInput($_POST['judul']);
    $tanggal = sanitizeInput($_POST['tanggal']);
    $waktu = sanitizeInput($_POST['waktu']);
    $lokasi = sanitizeInput($_POST['lokasi']);
    $isi_notula = sanitizeHTML($_POST['isi_notula']);
    $daftar_peserta = sanitizeInput($_POST['daftar_peserta']);
    $status = sanitizeInput($_POST['status']);
    
    $errors = [];
    
    if (empty($kelompok_id)) $errors[] = "Pilih kelompok";
    if (empty($judul)) $errors[] = "Judul harus diisi";
    if (empty($tanggal)) $errors[] = "Tanggal harus diisi";
    if (empty($waktu)) $errors[] = "Waktu harus diisi";
    if (empty($isi_notula)) $errors[] = "Isi notula harus diisi";
    
    if (empty($errors)) {
        // Simpan notula
        $stmt = $conn->prepare("
            INSERT INTO notula (kelompok_id, judul, tanggal, waktu, lokasi, isi_notula, daftar_peserta, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssssssi", $kelompok_id, $judul, $tanggal, $waktu, $lokasi, $isi_notula, $daftar_peserta, $status, $user_id);
        
        if ($stmt->execute()) {
            $notula_id = $conn->insert_id;
            setFlashMessage('success', 'Notula berhasil dibuat!');
            header("Location: notula_detail.php?id=$notula_id");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat menyimpan notula";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Notula - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            language: 'id_ID'
        });
    </script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="card" style="max-width: 900px; margin: 40px auto;">
            <div class="card-header">
                <h2>Buat Notula Baru</h2>
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
                        <label for="kelompok_id">Kelompok/Mata Kuliah *</label>
                        <select id="kelompok_id" name="kelompok_id" class="form-control" required>
                            <option value="">-- Pilih Kelompok --</option>
                            <?php while ($kelompok = $kelompok_list->fetch_assoc()): ?>
                                <option value="<?= $kelompok['id'] ?>">
                                    <?= htmlspecialchars($kelompok['nama_kelompok']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="judul">Judul Rapat/Materi Kuliah *</label>
                        <input type="text" id="judul" name="judul" class="form-control" 
                               placeholder="Contoh: Rapat Koordinasi Proyek Web" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="tanggal">Tanggal *</label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="waktu">Waktu *</label>
                            <input type="time" id="waktu" name="waktu" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lokasi">Lokasi</label>
                        <input type="text" id="lokasi" name="lokasi" class="form-control" 
                               placeholder="Contoh: Ruang Lab 2 / Zoom Meeting">
                    </div>

                    <div class="form-group">
                        <label for="daftar_peserta">Daftar Peserta</label>
                        <textarea id="daftar_peserta" name="daftar_peserta" class="form-control" rows="3"
                                  placeholder="Contoh: Andi, Budi, Citra, Deni"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="isi_notula">Isi Notula *</label>
                        <textarea id="isi_notula" name="isi_notula"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="draft">Draft</option>
                            <option value="final">Final</option>
                            <option value="perlu_revisi">Perlu Revisi</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Simpan Notula</button>
                        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>