<?php
// Sesuaikan dengan MySQL di laptop masing-masing.
// XAMPP/Laragon biasanya: port 3306, user root, password kosong.
// MAMP biasanya: port 8889, user root, password root.
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$db   = 'paw_jurnal2_lms';

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    exit('Koneksi database gagal. Cek MySQL sudah menyala, database.sql sudah di-import, dan isi connect.php sudah benar.');
}
