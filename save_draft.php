<?php
// save_draft.php - Save draft to session via AJAX
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data) {
        // Sanitize data before saving to session
        $_SESSION['notula_draft'] = [
            'kelompok_id' => filter_var($data['kelompok_id'], FILTER_VALIDATE_INT),
            'judul' => sanitizeInput($data['judul'] ?? ''),
            'tanggal' => sanitizeInput($data['tanggal'] ?? ''),
            'waktu' => sanitizeInput($data['waktu'] ?? ''),
            'lokasi' => sanitizeInput($data['lokasi'] ?? ''),
            'daftar_peserta' => sanitizeInput($data['daftar_peserta'] ?? ''),
            'isi_notula' => $data['isi_notula'] ?? '', // Will be sanitized on actual save
            'status' => sanitizeInput($data['status'] ?? 'draft'),
            'saved_at' => time()
        ];
        
        echo json_encode(['success' => true, 'message' => 'Draft saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
