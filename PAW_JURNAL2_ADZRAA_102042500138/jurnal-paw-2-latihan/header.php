<?php
// Sebelum include file ini, isi dulu variabel $judul di halaman pemanggil.
$pesan = $_SESSION['pesan'] ?? '';
$jenis_pesan = $_SESSION['jenis_pesan'] ?? 'sukses';
unset($_SESSION['pesan'], $_SESSION['jenis_pesan']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($judul) ?> | LMS Praktikum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="topbar">
    <div class="wrap">
        <a class="brand" href="index.php"><span class="brand-mark"></span>LMS Praktikum</a>
        <nav>
            <a class="nav-link" href="index.php">Beranda</a>
            <a class="nav-link" href="mata_kuliah.php">Mata kuliah</a>
            <a class="nav-link" href="materi.php">Materi</a>
            <a class="nav-link" href="tugas.php">Tugas</a>
        </nav>
    </div>
</header>
<main class="wrap">
<?php if ($pesan !== ''): ?>
    <?php if ($jenis_pesan === 'galat'): ?>
        <p class="notice notice-error" role="alert"><?= h($pesan) ?></p>
    <?php else: ?>
        <p class="notice notice-sukses" role="status"><?= h($pesan) ?></p>
    <?php endif ?>
<?php endif ?>
