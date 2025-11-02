<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Ambil semua notula dari kelompok yang diikuti user
$stmt = $conn->prepare("
    SELECT n.*, k.nama_kelompok, u.nama as creator_name
    FROM notula n
    JOIN kelompok k ON n.kelompok_id = k.id
    JOIN users u ON n.created_by = u.id
    JOIN user_kelompok uk ON k.id = uk.kelompok_id
    WHERE uk.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $user_id);
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
    <title>Daftar Notula - Platform Notula</title>
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
                <h2>Daftar Notula</h2>
                <a href="notula_create.php" class="btn btn-primary">+ Buat Notula Baru</a>
            </div>
            <div class="card-body">
                <?php if ($notula_list->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kelompok</th>
                                <th>Tanggal</th>
                                <th>Pembuat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($notula = $notula_list->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($notula['judul']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($notula['nama_kelompok']) ?></td>
                                    <td><?= date('d M Y', strtotime($notula['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($notula['creator_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $notula['status'] ?>">
                                            <?= ucfirst($notula['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="notula_detail.php?id=<?= $notula['id'] ?>" class="btn btn-sm btn-primary">Lihat</a>
                                        <?php if ($notula['created_by'] == $user_id): ?>
                                            <a href="notula_edit.php?id=<?= $notula['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted">Belum ada notula yang tersedia.</p>
                    <div class="text-center mt-20">
                        <a href="notula_create.php" class="btn btn-primary">Buat Notula Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>