CREATE DATABASE IF NOT EXISTS paw_jurnal2_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE paw_jurnal2_lms;

CREATE TABLE IF NOT EXISTS mata_kuliah (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    dosen VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    CONSTRAINT uq_mata_kuliah_kode UNIQUE (kode),
    CONSTRAINT ck_mata_kuliah_kode CHECK (CHAR_LENGTH(kode) >= 3),
    CONSTRAINT ck_mata_kuliah_nama CHECK (CHAR_LENGTH(nama) >= 3),
    CONSTRAINT ck_mata_kuliah_dosen CHECK (CHAR_LENGTH(dosen) >= 3),
    CONSTRAINT ck_mata_kuliah_semester CHECK (semester BETWEEN 1 AND 14)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    tautan VARCHAR(255) NOT NULL,
    CONSTRAINT fk_materi_mata_kuliah FOREIGN KEY (mata_kuliah_id)
        REFERENCES mata_kuliah (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT ck_materi_judul CHECK (CHAR_LENGTH(judul) >= 3),
    CONSTRAINT ck_materi_isi CHECK (CHAR_LENGTH(isi) BETWEEN 3 AND 2000),
    CONSTRAINT ck_materi_tautan CHECK (tautan LIKE 'http://%' OR tautan LIKE 'https://%')
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tugas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    judul VARCHAR(150) NOT NULL,
    instruksi TEXT NOT NULL,
    tenggat DATE NOT NULL,
    status ENUM('Draf', 'Aktif', 'Selesai') NOT NULL DEFAULT 'Draf',
    CONSTRAINT fk_tugas_mata_kuliah FOREIGN KEY (mata_kuliah_id)
        REFERENCES mata_kuliah (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT ck_tugas_judul CHECK (CHAR_LENGTH(judul) >= 3),
    CONSTRAINT ck_tugas_instruksi CHECK (CHAR_LENGTH(instruksi) BETWEEN 3 AND 2000),
    KEY idx_tugas_tenggat (tenggat)
) ENGINE=InnoDB;

INSERT IGNORE INTO mata_kuliah (id, kode, nama, dosen, semester) VALUES
(1, 'PAW-101', 'Pengembangan Aplikasi Website', 'Dosen PAW', 3),
(2, 'ALP-101', 'Algoritma dan Pemrograman', 'Dosen ALP', 1);

INSERT IGNORE INTO materi (id, mata_kuliah_id, judul, isi, tautan) VALUES
(1, 1, 'Operasi CRUD PHP dan MySQL', 'Pelajari penambahan, pembacaan, perubahan, dan penghapusan data melalui PHP.', 'https://www.php.net/manual/en/book.mysqli.php'),
(2, 2, 'Percabangan dan Perulangan', 'Gunakan kondisi dan perulangan untuk mengolah data dalam program.', 'https://www.php.net/manual/en/language.control-structures.php');

INSERT IGNORE INTO tugas (id, mata_kuliah_id, judul, instruksi, tenggat, status) VALUES
(1, 1, 'Membangun LMS sederhana', 'Bangun pengelolaan mata kuliah, materi, dan tugas dengan operasi CRUD yang dapat diuji.', '2026-11-01', 'Aktif'),
(2, 2, 'Latihan percabangan', 'Buat program pemilihan kondisi dan dokumentasikan contoh masukannya.', '2026-11-03', 'Draf');
