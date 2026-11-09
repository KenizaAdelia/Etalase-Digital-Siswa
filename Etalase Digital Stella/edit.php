<?php
require 'koneksi.php';
wajib_role(['super_admin', 'admin']);
$u = $_SESSION['user'];
$type = ($_GET['type'] ?? 'karya') === 'user' ? 'user' : 'karya';
$id = (int)($_GET['id'] ?? 0);
$err = '';

if ($type === 'karya') {
    $d = satu(q("SELECT * FROM projects WHERE id=?", 'i', [$id]));
    if (!$d) { flash("Karya tidak ditemukan.", "err"); header("Location: " . halaman_utama()); exit; }
} else {
    $d = satu(q("SELECT * FROM users WHERE id=?", 'i', [$id]));
    if (!$d || ($u['role'] === 'admin' && $d['role'] !== 'siswa')) { flash("Anda tidak berhak mengubah data tersebut.", "err"); header("Location: " . halaman_utama() . "?page=siswa"); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    if ($type === 'karya') {
        $judul = trim($_POST['judul'] ?? ''); $desk = trim($_POST['deskripsi'] ?? '');
        $lp = trim($_POST['link_pendukung'] ?? ''); $ld = trim($_POST['link_demo'] ?? '');
        $d['judul'] = $judul; $d['deskripsi'] = $desk; $d['link_pendukung'] = $lp; $d['link_demo'] = $ld;
        if ($judul === '' || $desk === '' || $lp === '') $err = "Judul, deskripsi, dan link pendukung wajib diisi.";
        elseif (!url_valid($lp)) $err = "Link pendukung harus diawali http:// atau https://";
        elseif ($ld !== '' && !url_valid($ld)) $err = "Link demo harus diawali http:// atau https://";
        else {
            $baru = upload_gambar($_FILES['gambar'] ?? null, $err, false);
            if (!$err) {
                $lama = $d['gambar'];
                $gambar = $baru ?: $lama;
                q("UPDATE projects SET judul=?, deskripsi=?, gambar=?, link_pendukung=?, link_demo=? WHERE id=?", 'sssssi',
                  [$judul, $desk, $gambar, $lp, $ld !== '' ? $ld : null, $id]);
                if ($baru) hapus_gambar($lama);
                flash("Karya berhasil diperbarui.");
                header("Location: " . halaman_utama()); exit;
            }
        }
    } else {
        $nama = trim($_POST['nama'] ?? ''); $username = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
        $role = $d['role'];
        if ($u['role'] === 'super_admin' && $id !== $u['id'] && in_array($_POST['role'] ?? '', ['super_admin', 'admin', 'siswa'])) $role = $_POST['role'];
        $d['nama'] = $nama; $d['username'] = $username; $d['role'] = $role;
        if ($nama === '' || $username === '') $err = "Nama dan username wajib diisi.";
        elseif (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) $err = "Username 4-30 karakter (huruf, angka, titik, garis bawah).";
        elseif ($pw !== '' && strlen($pw) < 6) $err = "Password baru minimal 6 karakter.";
        elseif (satu(q("SELECT id FROM users WHERE username=? AND id<>?", 'si', [$username, $id]))) $err = "Username sudah dipakai.";
        else {
            if ($pw !== '') q("UPDATE users SET nama=?, username=?, role=?, password=? WHERE id=?", 'ssssi', [$nama, $username, $role, password_hash($pw, PASSWORD_DEFAULT), $id]);
            else q("UPDATE users SET nama=?, username=?, role=? WHERE id=?", 'sssi', [$nama, $username, $role, $id]);
            if ($id === $u['id']) { $_SESSION['user']['nama'] = $nama; $_SESSION['user']['username'] = $username; }
            flash("Data pengguna berhasil diperbarui.");
            header("Location: " . halaman_utama() . "?page=siswa"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit <?= $type === 'karya' ? 'Karya' : 'Pengguna' ?> - Etalase Digital Stella</title>
<link rel="stylesheet" href="css/edit.css">
</head>
<body>
<?php navbar($type === 'karya' ? 'karya' : 'siswa'); ?>
<main class="wadah sempit">
  <form method="post" enctype="multipart/form-data" class="form-card">
    <h2>Edit <?= $type === 'karya' ? 'Karya' : 'Pengguna' ?></h2>
    <?php if ($err) echo '<div class="alert err">' . e($err) . '</div>'; ?>
    <?= csrf_field() ?>
    <?php if ($type === 'karya') { ?>
      <label>Judul Projek</label><input type="text" name="judul" value="<?= e($d['judul']) ?>" maxlength="150" required>
      <label>Deskripsi</label><textarea name="deskripsi" rows="5" required><?= e($d['deskripsi']) ?></textarea>
      <label>Gambar Karya <small>(kosongkan jika tidak diganti)</small></label>
      <?php if ($d['gambar']) echo '<img class="pratinjau" src="img/karya/' . e(rawurlencode($d['gambar'])) . '" alt="Gambar saat ini">'; ?>
      <input type="file" name="gambar" accept="image/*">
      <label>Link Pendukung</label><input type="url" name="link_pendukung" value="<?= e($d['link_pendukung']) ?>" required>
      <label>Link Demo <small>(opsional)</small></label><input type="url" name="link_demo" value="<?= e($d['link_demo']) ?>">
    <?php } else { ?>
      <label>Nama Lengkap</label><input type="text" name="nama" value="<?= e($d['nama']) ?>" required>
      <label>Username</label><input type="text" name="username" value="<?= e($d['username']) ?>" required>
      <label>Password Baru <small>(kosongkan jika tidak diganti)</small></label><input type="password" name="password">
      <?php if ($u['role'] === 'super_admin' && $id !== $u['id']) { ?>
        <label>Role</label>
        <select name="role">
          <?php foreach (['siswa', 'admin', 'super_admin'] as $r) echo '<option value="' . $r . '"' . ($d['role'] === $r ? ' selected' : '') . '>' . label_role($r) . '</option>'; ?>
        </select>
      <?php } ?>
    <?php } ?>
    <div class="tombol">
      <button type="submit" class="btn">Simpan Perubahan</button>
      <a class="btn batal" href="<?= halaman_utama() . ($type === 'user' ? '?page=siswa' : '') ?>">Batal</a>
    </div>
  </form>
</main>
</body>
</html>
