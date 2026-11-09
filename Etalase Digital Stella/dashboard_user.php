<?php
require 'koneksi.php';
wajib_role(['siswa']);
$u = $_SESSION['user'];
$cari = trim($_GET['q'] ?? '');
$like = '%' . $cari . '%';
$data = baris(q("SELECT p.*, u.nama AS penulis FROM projects p JOIN users u ON u.id=p.user_id
                 WHERE p.judul LIKE ? OR p.deskripsi LIKE ? OR u.nama LIKE ? ORDER BY p.id DESC", 'sss', [$like, $like, $like]));
$milik = satu(q("SELECT COUNT(*) n FROM projects WHERE user_id=?", 'i', [$u['id']]))['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Siswa - Etalase Digital Stella</title>
<link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<?php navbar('karya'); ?>
<main class="wadah">
  <section class="hero">
    <div>
      <h1>Halo, <?= e($u['nama']) ?> 👋</h1>
      <p>Tambahkan projekmu dan lihat karya teman-teman di katalog.</p>
    </div>
    <div class="statistik">
      <div><b><?= $milik ?></b><span>Karya Saya</span></div>
      <div><b><?= count($data) ?></b><span>Tampil</span></div>
    </div>
  </section>

  <?php tampil_flash(); ?>

  <div class="judul-bagian">
    <h2>Katalog Karya Siswa</h2>
    <a class="btn" href="tambah.php">+ Tambah Karya</a>
  </div>
  <?php kotak_cari($cari); ?>
  <?php if (!$data) echo '<p class="kosong">Belum ada karya yang cocok.</p>'; ?>
  <div class="grid">
    <?php foreach ($data as $p) kartu($p, false); ?>
  </div>
</main>
<footer class="kaki">© <?= date('Y') ?> Etalase Digital Stella · Keniza Adelia</footer>
</body>
</html>
