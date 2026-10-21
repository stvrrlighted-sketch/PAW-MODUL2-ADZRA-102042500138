<?php
// Halaman mata kuliah. Bagian ini SUDAH JADI dan bisa dipakai sebagai contoh
// untuk mengerjakan materi.php dan tugas.php. Yang perlu dikerjakan hanya TODO 10.
//
// Satu file ini melayani lima tampilan, dibedakan lewat ?aksi= di URL:
//   mata_kuliah.php                    daftar
//   mata_kuliah.php?aksi=tambah        form tambah
//   mata_kuliah.php?aksi=detail&id=1   detail
//   mata_kuliah.php?aksi=edit&id=1     form edit
//   mata_kuliah.php?aksi=hapus&id=1    konfirmasi hapus
require 'functions.php';
require 'connect.php';

$aksi = input($_GET, 'aksi');
if (!in_array($aksi, ['tambah', 'detail', 'edit', 'hapus'], true)) {
    $aksi = 'daftar';
}

$mk = ['kode' => '', 'nama' => '', 'dosen' => '', 'semester' => ''];
$errors = [];
$gagal = false;
$token_salah = false;
$id = 0;

// Detail, edit, dan hapus butuh data yang sedang dibuka.
if ($aksi === 'detail' || $aksi === 'edit' || $aksi === 'hapus') {
    $id = angka_id(input($_GET, 'id'));
    $mk = cari_mata_kuliah($conn, $id);
    if (!$mk) {
        pindah_halaman('mata_kuliah.php', 'Mata kuliah tidak ditemukan.', 'galat');
    }
}

// ---------- DAFTAR ----------
if ($aksi === 'daftar') {
    $search = input($_GET, 'search');

    // CONTOH READ dengan pencarian
    // Tanda ? diisi lewat execute(), jadi isi $search tidak pernah menempel langsung di query.
    $sql = 'SELECT * FROM mata_kuliah
            WHERE kode LIKE ? OR nama LIKE ? OR dosen LIKE ?
            ORDER BY kode';
    $kata = '%' . $search . '%';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kata, $kata, $kata]);
    $daftar_mk = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ---------- TAMBAH DAN EDIT ----------
if (($aksi === 'tambah' || $aksi === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mk = [
        'kode'     => strtoupper(input($_POST, 'kode')),
        'nama'     => input($_POST, 'nama'),
        'dosen'    => input($_POST, 'dosen'),
        'semester' => input($_POST, 'semester'),
    ];
    $errors = validasi_mata_kuliah($conn, $mk, $id);

    if (count($errors) === 0 && $aksi === 'tambah') {
        // CONTOH CREATE
        $stmt = $conn->prepare('INSERT INTO mata_kuliah (kode, nama, dosen, semester) VALUES (?, ?, ?, ?)');
        $stmt->execute([$mk['kode'], $mk['nama'], $mk['dosen'], $mk['semester']]);

        pindah_halaman('mata_kuliah.php', 'Mata kuliah berhasil ditambahkan.');
    }

    if (count($errors) === 0 && $aksi === 'edit') {
        // CONTOH UPDATE
        // Tanpa WHERE id = ?, semua baris di tabel akan ikut berubah.
        $stmt = $conn->prepare('UPDATE mata_kuliah SET kode = ?, nama = ?, dosen = ?, semester = ? WHERE id = ?');
        $stmt->execute([$mk['kode'], $mk['nama'], $mk['dosen'], $mk['semester'], $id]);

        pindah_halaman('mata_kuliah.php?aksi=detail&id=' . $id, 'Mata kuliah berhasil diperbarui.');
    }
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    // TODO 10 (RELASI)
    // Mata kuliah yang masih punya materi atau tugas tidak boleh dihapus,
    // karena materi dan tugas itu akan kehilangan induknya.
    // Hitung jumlah materi dan jumlah tugas milik mata kuliah ini (id = $id),
    // lalu simpan ke dua variabel di bawah.
    $jumlah_materi = hitung_milik_mata_kuliah($conn, 'materi', $id);
    $jumlah_tugas  = hitung_milik_mata_kuliah($conn, 'tugas', $id);

    $masih_dipakai = $jumlah_materi > 0 || $jumlah_tugas > 0;
    $token_salah = $_SERVER['REQUEST_METHOD'] === 'POST' && !token_valid();

    // Data baru dihapus setelah tombol konfirmasi ditekan (POST).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$masih_dipakai && !$token_salah) {
        try {
            // CONTOH DELETE
            $stmt = $conn->prepare('DELETE FROM mata_kuliah WHERE id = ?');
            $stmt->execute([$id]);

            pindah_halaman('mata_kuliah.php', 'Mata kuliah berhasil dihapus.');
        } catch (mysqli_sql_exception $e) {
            // Sampai di sini berarti database menolak, biasanya karena masih ada materi/tugas
            // yang terhubung (FOREIGN KEY). Kalau TODO 10 sudah benar, bagian ini tidak akan terpanggil.
            $gagal = true;
        }
    }
}

$judul = 'Mata kuliah';
require 'header.php';
?>

<?php if ($aksi === 'daftar'): ?>
    <div class="page-head">
        <div>
            <h1>Mata kuliah</h1>
            <p class="muted">Menampilkan <?= count($daftar_mk) ?> data.</p>
        </div>
        <a class="btn" href="mata_kuliah.php?aksi=tambah">+ Tambah</a>
    </div>

    <form class="toolbar" method="get" action="mata_kuliah.php">
        <input type="text" name="search" placeholder="Cari kode, nama, atau dosen" value="<?= h($search) ?>">
        <button class="btn" type="submit">Cari</button>
        <a class="btn btn-ghost" href="mata_kuliah.php">Reset</a>
    </form>

    <?php if (count($daftar_mk) === 0): ?>
        <div class="empty">
            <?php if ($search !== ''): ?>
                <p>Tidak ada mata kuliah yang cocok dengan pencarian "<?= h($search) ?>".</p>
                <a class="btn btn-ghost" href="mata_kuliah.php">Reset pencarian</a>
            <?php else: ?>
                <p>Belum ada mata kuliah. Tambahkan yang pertama untuk memulai.</p>
                <a class="btn" href="mata_kuliah.php?aksi=tambah">+ Tambah</a>
            <?php endif ?>
        </div>
    <?php else: ?>
        <div class="card list">
            <?php foreach ($daftar_mk as $baris): ?>
                <div class="list-item">
                    <div>
                        <p class="kicker"><span class="code"><?= h($baris['kode']) ?></span> | Semester <?= (int) $baris['semester'] ?></p>
                        <h2><?= h($baris['nama']) ?></h2>
                        <p class="muted"><?= h($baris['dosen']) ?></p>
                    </div>
                    <div class="actions">
                        <a class="btn btn-sm" href="mata_kuliah.php?aksi=detail&id=<?= (int) $baris['id'] ?>">Detail</a>
                        <a class="btn btn-sm btn-ghost" href="mata_kuliah.php?aksi=edit&id=<?= (int) $baris['id'] ?>">Edit</a>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

<?php elseif ($aksi === 'tambah' || $aksi === 'edit'): ?>
    <h1><?= $aksi === 'tambah' ? 'Tambah' : 'Edit' ?> mata kuliah</h1>

    <?php if (count($errors) > 0): ?>
        <p class="notice notice-error">Data belum tersimpan. Perbaiki kolom yang ditandai.</p>
    <?php endif ?>

    <?php // action dikosongkan supaya form terkirim ke alamat yang sedang dibuka (tambah atau edit). ?>
    <form class="card form" method="post" action="">
        <div class="fields">
            <div class="field">
                <label for="kode">Kode mata kuliah</label>
                <input class="kapital" type="text" id="kode" name="kode" maxlength="20" placeholder="PAW-101" value="<?= h($mk['kode']) ?>">
                <?php if (isset($errors['kode'])): ?>
                    <p class="field-error"><?= h($errors['kode']) ?></p>
                <?php endif ?>
            </div>

            <div class="field">
                <label for="semester">Semester</label>
                <input type="number" id="semester" name="semester" min="1" max="14" placeholder="3" value="<?= h($mk['semester']) ?>">
                <?php if (isset($errors['semester'])): ?>
                    <p class="field-error"><?= h($errors['semester']) ?></p>
                <?php endif ?>
            </div>

            <div class="field wide">
                <label for="nama">Nama mata kuliah</label>
                <input type="text" id="nama" name="nama" maxlength="100" placeholder="Pengembangan Aplikasi Website" value="<?= h($mk['nama']) ?>">
                <?php if (isset($errors['nama'])): ?>
                    <p class="field-error"><?= h($errors['nama']) ?></p>
                <?php endif ?>
            </div>

            <div class="field wide">
                <label for="dosen">Dosen pengampu</label>
                <input type="text" id="dosen" name="dosen" maxlength="100" placeholder="Nama dosen" value="<?= h($mk['dosen']) ?>">
                <?php if (isset($errors['dosen'])): ?>
                    <p class="field-error"><?= h($errors['dosen']) ?></p>
                <?php endif ?>
            </div>
        </div>
        <div class="actions">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn btn-ghost" href="<?= $aksi === 'edit' ? 'mata_kuliah.php?aksi=detail&id=' . $id : 'mata_kuliah.php' ?>">Batal</a>
        </div>
    </form>

<?php elseif ($aksi === 'detail'): ?>
    <article class="card detail">
        <p class="kicker"><span class="code"><?= h($mk['kode']) ?></span> | Semester <?= (int) $mk['semester'] ?></p>
        <h1><?= h($mk['nama']) ?></h1>

        <dl class="specs">
            <dt>Kode</dt>
            <dd><?= h($mk['kode']) ?></dd>
            <dt>Dosen pengampu</dt>
            <dd><?= h($mk['dosen']) ?></dd>
            <dt>Semester</dt>
            <dd><?= (int) $mk['semester'] ?></dd>
        </dl>

        <div class="actions">
            <a class="btn" href="mata_kuliah.php?aksi=edit&id=<?= $id ?>">Edit</a>
            <a class="btn btn-ghost btn-danger" href="mata_kuliah.php?aksi=hapus&id=<?= $id ?>">Hapus</a>
            <a class="btn btn-ghost" href="mata_kuliah.php">Kembali</a>
        </div>
    </article>

<?php elseif ($aksi === 'hapus'): ?>
    <?php if ($gagal): ?>
        <p class="notice notice-error" role="alert">Mata kuliah tidak dapat dihapus karena database menolak: masih ada materi atau tugas yang terhubung.</p>
    <?php endif ?>

    <?php if ($token_salah): ?>
        <p class="notice notice-error" role="alert">Token keamanan tidak valid atau sesi sudah berakhir. Muat ulang halaman lalu coba lagi.</p>
    <?php endif ?>

    <?php if ($masih_dipakai): ?>
        <p class="notice notice-error" role="alert">Mata kuliah tidak dapat dihapus karena masih memiliki <?= (int) $jumlah_materi ?> materi dan <?= (int) $jumlah_tugas ?> tugas.</p>
        <div class="card detail">
            <h1>Belum bisa dihapus</h1>
            <p class="muted"><b><?= h($mk['nama']) ?></b> masih terhubung dengan data lain. Hapus materi dan tugasnya, atau pindahkan ke mata kuliah lain lewat menu Edit.</p>
            <div class="actions">
                <?php if ($jumlah_materi > 0): ?>
                    <a class="btn btn-ghost" href="materi.php?mata_kuliah_id=<?= $id ?>">Lihat materi</a>
                <?php endif ?>
                <?php if ($jumlah_tugas > 0): ?>
                    <a class="btn btn-ghost" href="tugas.php?mata_kuliah_id=<?= $id ?>">Lihat tugas</a>
                <?php endif ?>
                <a class="btn" href="mata_kuliah.php?aksi=detail&id=<?= $id ?>">Kembali</a>
            </div>
        </div>
    <?php else: ?>
        <form class="card detail" method="post" action="">
            <?= bidang_token() ?>
            <h1>Hapus mata kuliah ini?</h1>
            <p class="muted"><b><?= h($mk['nama']) ?></b> dengan kode <b><?= h($mk['kode']) ?></b> akan dihapus dan tidak bisa dikembalikan.</p>
            <div class="actions">
                <button class="btn" type="submit">Ya, hapus</button>
                <a class="btn btn-ghost" href="mata_kuliah.php?aksi=detail&id=<?= $id ?>">Batal</a>
            </div>
        </form>
    <?php endif ?>
<?php endif ?>

<?php require 'footer.php'; ?>
