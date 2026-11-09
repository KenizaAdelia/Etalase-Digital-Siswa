<?php
require 'koneksi.php';
wajib_role(['super_admin', 'admin']);
$u = $_SESSION['user'];
$page = ($_GET['page'] ?? 'karya') === 'siswa' ? 'siswa' : 'karya';
$cari = trim($_GET['q'] ?? '');
$like = '%' . $cari . '%';

$total_karya = satu(q("SELECT COUNT(*) n FROM projects"))['n'];
$total_siswa = satu(q("SELECT COUNT(*) n FROM users WHERE role='siswa'"))['n'];
$total_admin = satu(q("SELECT COUNT(*) n FROM users WHERE role IN ('admin','super_admin')"))['n'];

if ($page === 'karya') {
    $data = baris(q("SELECT p.*, u.nama AS penulis FROM projects p JOIN users u ON u.id=p.user_id
                     WHERE p.judul LIKE ? OR p.deskripsi LIKE ? OR u.nama LIKE ? ORDER BY p.id DESC", 'sss', [$like, $like, $like]));
} else {
    $filter = $u['role'] === 'super_admin' ? "" : "AND role='siswa'";
    $data = baris(q("SELECT * FROM users WHERE (nama LIKE ? OR username LIKE ?) $filter ORDER BY FIELD(role,'super_admin','admin','siswa'), nama", 'ss', [$like, $like]));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard <?= label_role($u['role']) ?> - Etalase Digital Stella</title>
<link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<?php navbar($page); ?>
<main class="wadah">
  <section class="hero">
    <div>
      <h1>Halo, <?= e($u['nama']) ?> 👋</h1>
      <p>Anda masuk sebagai <b><?= label_role($u['role']) ?></b>. Kelola karya <?= $u['role'] === 'super_admin' ? 'dan seluruh pengguna' : 'dan data siswa' ?> di sini.</p>
    </div>
    <div class="statistik">
      <div><b><?= $total_karya ?></b><span>Karya</span></div>
      <div><b><?= $total_siswa ?></b><span>Siswa</span></div>
      <?php if ($u['role'] === 'super_admin') { ?><div><b><?= $total_admin ?></b><span>Admin</span></div><?php } ?>
    </div>
  </section>

  <?php tampil_flash(); ?>

  <?php if ($page === 'karya') { ?>
    <div class="judul-bagian">
      <h2>Katalog Karya Siswa</h2>
      <a class="btn" href="tambah.php">+ Tambah Karya</a>
    </div>
    <?php kotak_cari($cari); ?>
    <?php if (!$data) echo '<p class="kosong">Belum ada karya yang cocok.</p>'; ?>
    <div class="grid">
      <?php foreach ($data as $p) kartu($p, true); ?>
    </div>
  <?php } else { ?>
    <div class="judul-bagian">
      <h2><?= $u['role'] === 'super_admin' ? 'Kelola Pengguna' : 'Kelola Data Siswa' ?></h2>
      <a class="btn" href="tambah.php?type=user">+ Tambah <?= $u['role'] === 'super_admin' ? 'Pengguna' : 'Siswa' ?></a>
    </div>
    <?php kotak_cari($cari, 'siswa'); ?>
    <div class="tabel-bungkus">
    <table>
      <thead><tr><th>No</th><th>Nama</th><th>Username</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (!$data) echo '<tr><td colspan="6" class="kosong">Data tidak ditemukan.</td></tr>'; ?>
      <?php foreach ($data as $i => $r) {
          $boleh = $u['role'] === 'super_admin' || $r['role'] === 'siswa'; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($r['nama']) ?></td>
          <td><?= e($r['username']) ?></td>
          <td><span class="lencana <?= e($r['role']) ?>"><?= label_role($r['role']) ?></span></td>
          <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          <td class="aksi">
            <?php if ($boleh) { ?>
              <a class="btn kecil" href="edit.php?type=user&id=<?= (int)$r['id'] ?>">Edit</a>
              <?php if ((int)$r['id'] !== $u['id']) { ?>
              <form method="post" action="delete.php" onsubmit="return confirm('Hapus pengguna ini beserta semua karyanya?')">
                <?= csrf_field() ?><input type="hidden" name="type" value="user"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn kecil hapus">Hapus</button>
              </form>
              <?php } ?>
            <?php } else echo '-'; ?>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
    </div>
  <?php } ?>
</main>
<footer class="kaki">© <?= date('Y') ?> Etalase Digital Stella · Keniza Adelia</footer>
</body>
</html>
