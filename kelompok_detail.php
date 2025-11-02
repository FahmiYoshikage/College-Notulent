<?php
require_once 'config.php';
requireLogin();

$kelompok_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Ambil detail kelompok
$stmt = $conn->prepare("
    SELECT k.*, u.nama as creator_name, uk.role_kelompok
    FROM kelompok k
    JOIN users u ON k.created_by = u.id
    LEFT JOIN user_kelompok uk ON k.id = uk.kelompok_id AND uk.user_id = ?
    WHERE k.id = ?
");
$stmt->bind_param("ii", $user_id, $kelompok_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0 || $result->fetch_assoc()['role_kelompok'] === null) {
    setFlashMessage('error', 'Kelompok tidak ditemukan atau Anda tidak memiliki akses');
    header("Location: dashboard.php");
    exit();
}

$result->data_seek(0);
$kelompok = $result->fetch_assoc();

// Ambil anggota kelompok
$stmt = $conn->prepare("
    SELECT u.id, u.nama, u.email, u.role, uk.role_kelompok, uk.joined_at
    FROM users u
    JOIN user_kelompok uk ON u.id = uk.user_id
    WHERE uk.kelompok_id = ?
    ORDER BY 
        CASE uk.role_kelompok 
            WHEN 'ketua' THEN 1 
            WHEN 'sekretaris' THEN 2 
            ELSE 3 
        END,
        u.nama
");
$stmt->bind_param("i", $kelompok_id);
$stmt->execute();
$anggota_list = $stmt->get_result();

// Ambil notula kelompok
$stmt = $conn->prepare("
    SELECT n.*, u.nama as creator_name
    FROM notula n
    JOIN users u ON n.created_by = u.id
    WHERE n.kelompok_id = ?
    ORDER BY n.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $kelompok_id);
$stmt->execute();
$notula_list = $stmt->get_result();

$flash = getFlashMessage();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kelompok['nama_kelompok']) ?> - Platform Notula</title>
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
                <div>
                    <h2><?= htmlspecialchars($kelompok['nama_kelompok']) ?></h2>
                    <p class="text-muted">
                        Kode Kelompok: <strong><?= $kelompok['kode_kelompok'] ?></strong> | 
                        Role: <strong><?= ucfirst($kelompok['role_kelompok']) ?></strong>
                    </p>
                </div>
                <a href="notula_create.php?kelompok=<?= $kelompok_id ?>" class="btn btn-primary">
                    + Buat Notula
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($kelompok['deskripsi'])): ?>
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>Deskripsi:</strong><br>
                        <?= nl2br(htmlspecialchars($kelompok['deskripsi'])) ?>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Anggota Kelompok -->
                    <div>
                        <h3 style="margin-bottom: 15px;">Anggota Kelompok (<?= $anggota_list->num_rows ?>)</h3>
                        <div class="list-group">
                            <?php while ($anggota = $anggota_list->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <strong><?= htmlspecialchars($anggota['nama']) ?></strong>
                                        <p class="text-muted">
                                            <?= htmlspecialchars($anggota['email']) ?> | 
                                            <?= ucfirst($anggota['role_kelompok']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Notula Terbaru -->
                    <div>
                        <h3 style="margin-bottom: 15px;">Notula Terbaru</h3>
                        <?php if ($notula_list->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($notula = $notula_list->fetch_assoc()): ?>
                                    <div class="list-item">
                                        <div>
                                            <strong><?= htmlspecialchars($notula['judul']) ?></strong>
                                            <p class="text-muted">
                                                <?= date('d M Y', strtotime($notula['tanggal'])) ?> | 
                                                <span class="badge badge-<?= $notula['status'] ?>">
                                                    <?= ucfirst($notula['status']) ?>
                                                </span>
                                            </p>
                                        </div>
                                        <a href="notula_detail.php?id=<?= $notula['id'] ?>" class="btn btn-sm">Lihat</a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">Belum ada notula di kelompok ini.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <a href="dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>