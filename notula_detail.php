<?php
require_once 'config.php';
requireLogin();

$notula_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Ambil detail notula dengan cek akses
$stmt = $conn->prepare("
    SELECT n.*, k.nama_kelompok, k.kode_kelompok, u.nama as creator_name,
           uk.user_id as has_access
    FROM notula n
    JOIN kelompok k ON n.kelompok_id = k.id
    JOIN users u ON n.created_by = u.id
    LEFT JOIN user_kelompok uk ON k.id = uk.kelompok_id AND uk.user_id = ?
    WHERE n.id = ?
");
$stmt->bind_param("ii", $user_id, $notula_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Notula tidak ditemukan atau Anda tidak memiliki akses');
    header("Location: dashboard.php");
    exit();
}

$notula = $result->fetch_assoc();

// Cek apakah user punya akses
if ($notula['has_access'] === null) {
    // Cek akses khusus
    $stmt = $conn->prepare("SELECT id FROM notula_akses WHERE notula_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notula_id, $user_id);
    $stmt->execute();
    $akses_result = $stmt->get_result();
    
    if ($akses_result->num_rows === 0) {
        setFlashMessage('error', 'Anda tidak memiliki akses ke notula ini');
        header("Location: dashboard.php");
        exit();
    }
}

$flash = getFlashMessage();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($notula['judul']) ?> - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notula-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .notula-meta-item {
            display: flex;
            flex-direction: column;
        }
        .notula-meta-label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .notula-meta-value {
            font-size: 14px;
            color: #333;
        }
        .notula-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #e1e8ed;
            line-height: 1.8;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 900px; margin: 40px auto;">
            <div class="card-header">
                <h2><?= htmlspecialchars($notula['judul']) ?></h2>
                <span class="badge badge-<?= $notula['status'] ?>">
                    <?= ucfirst($notula['status']) ?>
                </span>
            </div>
            
            <div class="card-body">
                <div class="notula-meta">
                    <div class="notula-meta-item">
                        <span class="notula-meta-label">Kelompok</span>
                        <span class="notula-meta-value"><?= htmlspecialchars($notula['nama_kelompok']) ?></span>
                    </div>
                    <div class="notula-meta-item">
                        <span class="notula-meta-label">Tanggal</span>
                        <span class="notula-meta-value"><?= date('d F Y', strtotime($notula['tanggal'])) ?></span>
                    </div>
                    <div class="notula-meta-item">
                        <span class="notula-meta-label">Waktu</span>
                        <span class="notula-meta-value"><?= date('H:i', strtotime($notula['waktu'])) ?> WIB</span>
                    </div>
                    <div class="notula-meta-item">
                        <span class="notula-meta-label">Lokasi</span>
                        <span class="notula-meta-value"><?= htmlspecialchars($notula['lokasi'] ?: '-') ?></span>
                    </div>
                    <div class="notula-meta-item">
                        <span class="notula-meta-label">Pembuat</span>
                        <span class="notula-meta-value"><?= htmlspecialchars($notula['creator_name']) ?></span>
                    </div>
                    <div class="notula-meta-item">
                        <span class="notula-meta-label">Dibuat</span>
                        <span class="notula-meta-value"><?= date('d M Y H:i', strtotime($notula['created_at'])) ?></span>
                    </div>
                </div>

                <?php if (!empty($notula['daftar_peserta'])): ?>
                    <div style="margin-bottom: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px; color: #333;">Daftar Peserta</h3>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($notula['daftar_peserta'])) ?></p>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; margin-bottom: 15px; color: #333;">Isi Notula</h3>
                    <div class="notula-content">
                        <?= $notula['isi_notula'] ?>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="notula_export.php?id=<?= $notula['id'] ?>" class="btn btn-success" target="_blank">
                        📄 Ekspor ke PDF
                    </a>
                    
                    <?php if ($notula['created_by'] == $user_id): ?>
                        <a href="notula_edit.php?id=<?= $notula['id'] ?>" class="btn btn-primary">
                            ✏️ Edit
                        </a>
                        <a href="notula_delete.php?id=<?= $notula['id'] ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Yakin ingin menghapus notula ini?')">
                            🗑️ Hapus
                        </a>
                    <?php endif; ?>
                    
                    <a href="notula_list.php" class="btn btn-secondary">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>