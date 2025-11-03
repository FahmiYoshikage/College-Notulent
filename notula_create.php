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
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    $kelompok_id = filter_var($_POST['kelompok_id'], FILTER_VALIDATE_INT);
    $judul = sanitizeInput($_POST['judul']);
    $tanggal = sanitizeInput($_POST['tanggal']);
    $waktu = sanitizeInput($_POST['waktu']);
    $lokasi = sanitizeInput($_POST['lokasi']);
    $isi_notula = sanitizeHTML($_POST['isi_notula']);
    $daftar_peserta = sanitizeInput($_POST['daftar_peserta']);
    $status = sanitizeInput($_POST['status']);
    
    $errors = [];
    
    if (!$kelompok_id || $kelompok_id <= 0) $errors[] = "Pilih kelompok";
    if (empty($judul)) $errors[] = "Judul harus diisi";
    if (empty($tanggal)) $errors[] = "Tanggal harus diisi";
    if (empty($waktu)) $errors[] = "Waktu harus diisi";
    if (empty($isi_notula)) $errors[] = "Isi notula harus diisi";
    if (!in_array($status, ['draft', 'final', 'perlu_revisi'])) $errors[] = "Status tidak valid";
    
    if (empty($errors)) {
        // Simpan notula
        $stmt = $conn->prepare("
            INSERT INTO notula (kelompok_id, judul, tanggal, waktu, lokasi, isi_notula, daftar_peserta, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssssssi", $kelompok_id, $judul, $tanggal, $waktu, $lokasi, $isi_notula, $daftar_peserta, $status, $user_id);
        
        if ($stmt->execute()) {
            $notula_id = $conn->insert_id;
            
            // Clear draft from session after successful save
            unset($_SESSION['notula_draft']);
            
            setFlashMessage('success', 'Notula berhasil dibuat!');
            header("Location: notula_detail.php?id=$notula_id");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat menyimpan notula";
        }
        
        $stmt->close();
    } else {
        // Save errors to session for display after redirect
        $_SESSION['notula_errors'] = $errors;
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve draft from session if exists
$draft = isset($_SESSION['notula_draft']) ? $_SESSION['notula_draft'] : [];

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
    <script src="https://cdn.tiny.cloud/1/<?= TINYMCE_API_KEY ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Auto-save draft function
        function saveDraft() {
            const formData = {
                kelompok_id: document.getElementById('kelompok_id').value,
                judul: document.getElementById('judul').value,
                tanggal: document.getElementById('tanggal').value,
                waktu: document.getElementById('waktu').value,
                lokasi: document.getElementById('lokasi').value,
                daftar_peserta: document.getElementById('daftar_peserta').value,
                isi_notula: tinymce.get('isi_notula') ? tinymce.get('isi_notula').getContent() : '',
                status: document.getElementById('status').value,
                timestamp: new Date().getTime()
            };
            
            // Save to localStorage
            localStorage.setItem('notula_draft', JSON.stringify(formData));
            
            // Save to server session via AJAX
            fetch('save_draft.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            // Show saved indicator
            const indicator = document.getElementById('save-indicator');
            if (indicator) {
                indicator.textContent = 'Draft tersimpan ' + new Date().toLocaleTimeString();
                indicator.style.color = '#28a745';
            }
        }
        
        // Restore draft from localStorage
        function restoreDraft() {
            const draft = localStorage.getItem('notula_draft');
            if (draft) {
                const data = JSON.parse(draft);
                
                // Check if draft is not too old (24 hours)
                const age = new Date().getTime() - (data.timestamp || 0);
                if (age < 24 * 60 * 60 * 1000) {
                    if (confirm('Ditemukan draft yang belum tersimpan. Restore draft?')) {
                        document.getElementById('kelompok_id').value = data.kelompok_id || '';
                        document.getElementById('judul').value = data.judul || '';
                        document.getElementById('tanggal').value = data.tanggal || '';
                        document.getElementById('waktu').value = data.waktu || '';
                        document.getElementById('lokasi').value = data.lokasi || '';
                        document.getElementById('daftar_peserta').value = data.daftar_peserta || '';
                        document.getElementById('status').value = data.status || 'draft';
                        
                        // Wait for TinyMCE to initialize before setting content
                        if (tinymce.get('isi_notula')) {
                            tinymce.get('isi_notula').setContent(data.isi_notula || '');
                        }
                    } else {
                        localStorage.removeItem('notula_draft');
                    }
                }
            }
        }
        
        // Clear draft when form is successfully submitted
        function clearDraft() {
            localStorage.removeItem('notula_draft');
        }
        
        // Initialize TinyMCE
        tinymce.init({
            selector: '#isi_notula',
            height: 400,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount', 'autosave'
            ],
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | table | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            language: 'id_ID',
            setup: function(editor) {
                editor.on('init', function() {
                    // Restore draft after editor is ready
                    restoreDraft();
                });
                
                editor.on('change keyup', function() {
                    // Auto-save every change (debounced)
                    clearTimeout(window.autoSaveTimeout);
                    window.autoSaveTimeout = setTimeout(saveDraft, 2000);
                });
            }
        });
        
        // Auto-save on input changes
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['kelompok_id', 'judul', 'tanggal', 'waktu', 'lokasi', 'daftar_peserta', 'status'];
            inputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', function() {
                        clearTimeout(window.autoSaveTimeout);
                        window.autoSaveTimeout = setTimeout(saveDraft, 2000);
                    });
                }
            });
            
            // Clear draft on form submit
            document.querySelector('form').addEventListener('submit', clearDraft);
        });
    </script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="card" style="max-width: 900px; margin: 40px auto;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Buat Notula Baru</h2>
                <small id="save-indicator" style="color: #6c757d; font-style: italic;">Auto-save aktif</small>
            </div>
            <div class="card-body">
                <?php 
                $errors = isset($_SESSION['notula_errors']) ? $_SESSION['notula_errors'] : [];
                unset($_SESSION['notula_errors']);
                if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
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