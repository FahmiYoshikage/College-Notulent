-- Database: notula_platform

CREATE DATABASE IF NOT EXISTS notula_platform;
USE notula_platform;

-- Tabel Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('mahasiswa', 'dosen', 'admin') DEFAULT 'mahasiswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kelompok/Mata Kuliah
CREATE TABLE kelompok (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kelompok VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    kode_kelompok VARCHAR(20) UNIQUE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Relasi User-Kelompok
CREATE TABLE user_kelompok (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kelompok_id INT NOT NULL,
    role_kelompok ENUM('anggota', 'ketua', 'sekretaris') DEFAULT 'anggota',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelompok_id) REFERENCES kelompok(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_kelompok (user_id, kelompok_id)
);

-- Tabel Notula
CREATE TABLE notula (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kelompok_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    lokasi VARCHAR(150),
    isi_notula LONGTEXT NOT NULL,
    daftar_peserta TEXT,
    status ENUM('draft', 'final', 'perlu_revisi') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kelompok_id) REFERENCES kelompok(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FULLTEXT KEY idx_isi_notula (isi_notula, judul)
);

-- Tabel Hak Akses Notula
CREATE TABLE notula_akses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notula_id INT NOT NULL,
    user_id INT NOT NULL,
    akses_type ENUM('view', 'edit') DEFAULT 'view',
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notula_id) REFERENCES notula(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_notula_akses (notula_id, user_id)
);

-- Tabel Riwayat Revisi (Opsional)
CREATE TABLE notula_revisi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notula_id INT NOT NULL,
    isi_notula_lama LONGTEXT NOT NULL,
    status_lama ENUM('draft', 'final', 'perlu_revisi'),
    revised_by INT NOT NULL,
    revised_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    keterangan VARCHAR(255),
    FOREIGN KEY (notula_id) REFERENCES notula(id) ON DELETE CASCADE,
    FOREIGN KEY (revised_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert data contoh
INSERT INTO users (nama, email, password, role) VALUES
('Admin System', 'admin@notula.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. Budi Santoso', 'budi@univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen'),
('Andi Wijaya', 'andi@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa');