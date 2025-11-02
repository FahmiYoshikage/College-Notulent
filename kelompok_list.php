<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Ambil semua kelompok yang diikuti user
$stmt = $conn->prepare("
    SELECT k.*, u.nama as creator_name, uk.role_kelompok,
           COUNT(DISTINCT uk2.user_id) as jumlah_anggota,
           COUNT(DISTINCT n.id) as jumlah_notula
    FROM kelompok k
    JOIN user_kelompok uk ON k.id = uk.kelompok_id
    JOIN users u ON k.created_by = u.id
    LEFT JOIN user_kelompok uk2 ON k.id = uk2.kelompok_id
    LEFT JOIN notula n ON k.id = n.kelompok_id
    WHERE uk.user_id = ?
    GROUP BY k.id
    ORDER BY k.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kelompok_list = $stmt->get_result();

$flash = getFlashMessage();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kelompok - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Daftar Kelompok</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="kelompok_create.php" class="btn btn-primary">+ Buat Kelompok</a>
                    <a href="kelompok_join.php" class="btn btn-secondary">Gabung Kelompok</a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($kelompok_list->num_rows > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                        <?php while ($kelompok = $kelompok_list->fetch_assoc()): ?>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #e1e8ed;">
                                <h3 style="margin-bottom: 10px; color: #333;">
                                    <?= htmlspecialchars($kelompok['nama_kelompok']) ?>
                                </h3>
                                <p class="text-muted" style="margin-bottom: 15px;">
                                    Kode: <strong><?= $kelompok['kode_kelompok'] ?></strong>
                                </p>
                                
                                <?php if (!empty($kelompok['deskripsi'])): ?>
                                    <p style="margin-bottom: 15px; color: #666; font-size: 14px;">
                                        <?= htmlspecialchars(substr($kelompok['deskripsi'], 0, 100)) ?>
                                        <?= strlen($kelompok['deskripsi']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>

                                <div style="display: flex; gap: 15px; margin-bottom: 15px; font-size: 14px; color: #666;">
                                    <span>👥 <?= $kelompok['jumlah_anggota'] ?> Anggota</span>
                                    <span>📝 <?= $kelompok['jumlah_notula'] ?> Notula</span>
                                </div>

                                <div style="padding-top: 15px; border-top: 1px solid #e1e8ed;">
                                    <span class="badge badge-draft" style="background: #667eea; color: white;">
                                        <?= ucfirst($kelompok['role_kelompok']) ?>
                                    </span>
                                </div>

                                <div style="margin-top: 15px;">
                                    <a href="kelompok_detail.php?id=<?= $kelompok['id'] ?>" class="btn btn-sm btn-primary">
                                        Lihat Detail
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Anda belum bergabung di kelompok manapun.</p>
                    <div class="text-center mt-20">
                        <a href="kelompok_create.php" class="btn btn-primary">Buat Kelompok Baru</a>
                        <a href="kelompok_join.php" class="btn btn-secondary">Gabung Kelompok</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
