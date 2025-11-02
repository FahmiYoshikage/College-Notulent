<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$search_results = null;
$search_query = '';

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = sanitizeInput($_GET['q']);
    
    // Pencarian dengan Full-Text Search atau LIKE
    $stmt = $conn->prepare("
        SELECT n.*, k.nama_kelompok, u.nama as creator_name
        FROM notula n
        JOIN kelompok k ON n.kelompok_id = k.id
        JOIN users u ON n.created_by = u.id
        JOIN user_kelompok uk ON k.id = uk.kelompok_id
        WHERE uk.user_id = ?
        AND (
            n.judul LIKE ? OR
            n.isi_notula LIKE ? OR
            n.daftar_peserta LIKE ? OR
            k.nama_kelompok LIKE ?
        )
        ORDER BY n.created_at DESC
    ");
    
    $like_query = '%' . $search_query . '%';
    $stmt->bind_param("issss", $user_id, $like_query, $like_query, $like_query, $like_query);
    $stmt->execute();
    $search_results = $stmt->get_result();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Notula - Platform Notula</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-box {
            max-width: 600px;
            margin: 30px auto;
        }
        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-form input {
            flex: 1;
        }
        .search-highlight {
            background: yellow;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="search-box">
            <h1 style="text-align: center; margin-bottom: 20px;">Pencarian Notula</h1>
            <form method="GET" class="search-form">
                <input type="text" name="q" class="form-control" 
                       placeholder="Cari berdasarkan judul, isi notula, peserta, atau kelompok..." 
                       value="<?= htmlspecialchars($search_query) ?>" required>
                <button type="submit" class="btn btn-primary">🔍 Cari</button>
            </form>
        </div>

        <?php if ($search_results !== null): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Hasil Pencarian</h2>
                    <span class="text-muted">
                        Ditemukan <?= $search_results->num_rows ?> notula untuk "<?= htmlspecialchars($search_query) ?>"
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($search_results->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($notula = $search_results->fetch_assoc()): ?>
                                <div class="list-item">
                                    <div style="flex: 1;">
                                        <h3><?= htmlspecialchars($notula['judul']) ?></h3>
                                        <p class="text-muted">
                                            <?= htmlspecialchars($notula['nama_kelompok']) ?> | 
                                            <?= date('d M Y', strtotime($notula['tanggal'])) ?> | 
                                            <span class="badge badge-<?= $notula['status'] ?>">
                                                <?= ucfirst($notula['status']) ?>
                                            </span>
                                        </p>
                                        <p style="margin-top: 10px; color: #666; font-size: 14px;">
                                            <?php
                                            // Tampilkan preview isi notula
                                            $preview = strip_tags($notula['isi_notula']);
                                            $preview = substr($preview, 0, 200);
                                            echo htmlspecialchars($preview) . '...';
                                            ?>
                                        </p>
                                    </div>
                                    <a href="notula_detail.php?id=<?= $notula['id'] ?>" class="btn btn-sm btn-primary">
                                        Lihat Detail
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">
                            Tidak ditemukan notula yang cocok dengan pencarian Anda.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>