<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Ambil kelompok yang diikuti user
$stmt = $conn->prepare("
    SELECT k.*, u.nama as creator_name, uk.role_kelompok
    FROM kelompok k
    JOIN user_kelompok uk ON k.id = uk.kelompok_id
    JOIN users u ON k.created_by = u.id
    WHERE uk.user_id = ?
    ORDER BY k.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kelompok_list = $stmt->get_result();

// Ambil notula terbaru
$stmt = $conn->prepare("
    SELECT n.*, k.nama_kelompok, u.nama as creator_name
    FROM notula n
    JOIN kelompok k ON n.kelompok_id = k.id
    JOIN users u ON n.created_by = u.id
    JOIN user_kelompok uk ON k.id = uk.kelompok_id
    WHERE uk.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notula_list = $stmt->get_result();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Platform Notula</title>
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

        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <p>Selamat datang, <strong><?= $_SESSION['nama'] ?></strong></p>
        </div>

        <div class="dashboard-grid">
            <!-- Kelompok Section -->
            <div class="card">
                <div class="card-header">
                    <h2>Kelompok Saya</h2>
                    <a href="kelompok_create.php" class="btn btn-sm btn-primary">+ Buat Kelompok</a>
                </div>
                <div class="card-body">
                    <?php if ($kelompok_list->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($kelompok = $kelompok_list->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <h3><?= htmlspecialchars($kelompok['nama_kelompok']) ?></h3>
                                        <p class="text-muted">
                                            Kode: <?= $kelompok['kode_kelompok'] ?> | 
                                            Role: <?= ucfirst($kelompok['role_kelompok']) ?>
                                        </p>
                                    </div>
                                    <a href="kelompok_detail.php?id=<?= $kelompok['id'] ?>" class="btn btn-sm">Lihat</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">Anda belum bergabung di kelompok manapun.</p>
                        <div class="text-center mt-20">
                            <a href="kelompok_join.php" class="btn btn-secondary">Gabung Kelompok</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notula Terbaru Section -->
            <div class="card">
                <div class="card-header">
                    <h2>Notula Terbaru</h2>
                    <a href="notula_create.php" class="btn btn-sm btn-primary">+ Buat Notula</a>
                </div>
                <div class="card-body">
                    <?php if ($notula_list->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($notula = $notula_list->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div>
                                        <h3><?= htmlspecialchars($notula['judul']) ?></h3>
                                        <p class="text-muted">
                                            <?= $notula['nama_kelompok'] ?> | 
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
                        <p class="text-center text-muted">Belum ada notula.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>