<?php
// Kumpulan fungsi bantu. File ini tidak perlu diubah,
// tapi baca dulu karena beberapa query di sini bisa jadi contoh untuk TODO.

session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$pilihan_status = ['Draf', 'Aktif', 'Selesai'];

// Wajib dipakai setiap menampilkan data ke HTML supaya aman dari XSS.
function h($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Ambil satu nilai dari $_GET atau $_POST, sekalian di-trim.
function input($sumber, $nama)
{
    if (isset($sumber[$nama]) && is_string($sumber[$nama])) {
        return str_replace("\r\n", "\n", trim($sumber[$nama]));
    }
    return '';
}

// Pindah halaman, bisa sambil menitip pesan yang tampil sekali di halaman tujuan.
function pindah_halaman($tujuan, $pesan = '', $jenis = 'sukses')
{
    if ($pesan !== '') {
        $_SESSION['pesan'] = $pesan;
        $_SESSION['jenis_pesan'] = $jenis;
    }
    header('Location: ' . $tujuan);
    exit;
}

// Daftar mata kuliah untuk isi dropdown.
// Hasilnya array dengan id sebagai key, contoh: [1 => 'PAW-101 - Pengembangan Aplikasi Website']
function pilihan_mata_kuliah($conn)
{
    $pilihan = [];
    $hasil = $conn->query('SELECT id, kode, nama FROM mata_kuliah ORDER BY kode');
    foreach ($hasil as $baris) {
        $pilihan[$baris['id']] = $baris['kode'] . ' - ' . $baris['nama'];
    }
    return $pilihan;
}

function cari_mata_kuliah($conn, $id)
{
    $stmt = $conn->prepare('SELECT * FROM mata_kuliah WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->get_result()->fetch_assoc();
}

// Contoh JOIN: materi diambil bersama kode dan nama mata kuliahnya.
function cari_materi($conn, $id)
{
    $sql = 'SELECT materi.*, mata_kuliah.kode AS kode_mk, mata_kuliah.nama AS nama_mk
            FROM materi
            JOIN mata_kuliah ON mata_kuliah.id = materi.mata_kuliah_id
            WHERE materi.id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->get_result()->fetch_assoc();
}

function cari_tugas($conn, $id)
{
    $sql = 'SELECT tugas.*, mata_kuliah.kode AS kode_mk, mata_kuliah.nama AS nama_mk
            FROM tugas
            JOIN mata_kuliah ON mata_kuliah.id = tugas.mata_kuliah_id
            WHERE tugas.id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->get_result()->fetch_assoc();
}

// Cek apakah kode sudah dipakai mata kuliah lain.
// $id_sendiri diisi waktu edit, supaya kode miliknya sendiri tidak dianggap bentrok.
function kode_sudah_dipakai($conn, $kode, $id_sendiri = 0)
{
    $stmt = $conn->prepare('SELECT id FROM mata_kuliah WHERE kode = ? AND id <> ?');
    $stmt->execute([$kode, $id_sendiri]);
    return $stmt->get_result()->num_rows > 0;
}

// Tiga fungsi validasi di bawah mengembalikan array pesan error.
// Kalau array-nya kosong berarti data valid.

function validasi_mata_kuliah($conn, $data, $id_sendiri = 0)
{
    $errors = [];

    if (!preg_match('/^[A-Z0-9]+(-[A-Z0-9]+)*$/', $data['kode']) || strlen($data['kode']) < 3 || strlen($data['kode']) > 20) {
        $errors['kode'] = 'Kode harus 3 sampai 20 karakter, hanya huruf besar, angka, dan tanda -. Contoh: PAW-101.';
    } elseif (kode_sudah_dipakai($conn, $data['kode'], $id_sendiri)) {
        $errors['kode'] = 'Kode mata kuliah sudah digunakan.';
    }

    if (mb_strlen($data['nama']) < 3 || mb_strlen($data['nama']) > 100) {
        $errors['nama'] = 'Nama harus 3 sampai 100 karakter.';
    }

    if (mb_strlen($data['dosen']) < 3 || mb_strlen($data['dosen']) > 100) {
        $errors['dosen'] = 'Nama dosen harus 3 sampai 100 karakter.';
    }

    if (!ctype_digit($data['semester']) || $data['semester'] < 1 || $data['semester'] > 14) {
        $errors['semester'] = 'Semester harus angka 1 sampai 14.';
    }

    return $errors;
}

function validasi_materi($data, $pilihan_mk)
{
    $errors = [];

    if (!isset($pilihan_mk[$data['mata_kuliah_id']])) {
        $errors['mata_kuliah_id'] = 'Pilih mata kuliah yang tersedia.';
    }

    if (mb_strlen($data['judul']) < 3 || mb_strlen($data['judul']) > 150) {
        $errors['judul'] = 'Judul harus 3 sampai 150 karakter.';
    }

    if (mb_strlen($data['isi']) < 3 || mb_strlen($data['isi']) > 2000) {
        $errors['isi'] = 'Isi materi harus 3 sampai 2000 karakter.';
    }

    if (!filter_var($data['tautan'], FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $data['tautan']) || strlen($data['tautan']) > 255) {
        $errors['tautan'] = 'Tautan harus URL lengkap yang diawali http:// atau https://, maksimal 255 karakter.';
    }

    return $errors;
}

function validasi_tugas($data, $pilihan_mk)
{
    global $pilihan_status;
    $errors = [];

    if (!isset($pilihan_mk[$data['mata_kuliah_id']])) {
        $errors['mata_kuliah_id'] = 'Pilih mata kuliah yang tersedia.';
    }

    if (mb_strlen($data['judul']) < 3 || mb_strlen($data['judul']) > 150) {
        $errors['judul'] = 'Judul harus 3 sampai 150 karakter.';
    }

    if (mb_strlen($data['instruksi']) < 3 || mb_strlen($data['instruksi']) > 2000) {
        $errors['instruksi'] = 'Instruksi harus 3 sampai 2000 karakter.';
    }

    // Format dari input type="date" adalah tahun-bulan-tanggal, contoh 2026-11-01.
    $bagian = explode('-', $data['tenggat']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tenggat']) || !checkdate((int) $bagian[1], (int) $bagian[2], (int) $bagian[0]) || $bagian[0] < 2000) {
        $errors['tenggat'] = 'Tenggat harus tanggal yang valid.';
    }

    if (!in_array($data['status'], $pilihan_status, true)) {
        $errors['status'] = 'Pilih status yang tersedia.';
    }

    return $errors;
}

function angka_id($nilai)
{
    if (is_string($nilai) && ctype_digit($nilai) && (int) $nilai > 0) {
        return (int) $nilai;
    }
    return 0;
}

// Karakter % dan _ dari pengguna diberi escape supaya dibaca apa adanya oleh LIKE.
function escape_like($teks)
{
    return addcslashes($teks, '%_\\');
}

function token_csrf()
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

function bidang_token()
{
    return '<input type="hidden" name="token" value="' . h(token_csrf()) . '">';
}

function token_valid()
{
    $kiriman = input($_POST, 'token');
    return $kiriman !== '' && isset($_SESSION['token_csrf']) && hash_equals($_SESSION['token_csrf'], $kiriman);
}

// $tabel dibatasi daftar putih karena nama tabel tidak bisa diisi lewat bind_param.
function hitung_milik_mata_kuliah($conn, $tabel, $id_mk)
{
    if (!in_array($tabel, ['materi', 'tugas'], true)) {
        return 0;
    }
    $stmt = $conn->prepare('SELECT COUNT(*) FROM ' . $tabel . ' WHERE mata_kuliah_id = ?');
    $stmt->bind_param('i', $id_mk);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_row()[0];
}
