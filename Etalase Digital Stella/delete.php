<?php
require 'koneksi.php';
wajib_role(['super_admin', 'admin']);
$u = $_SESSION['user'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: " . halaman_utama()); exit; }
csrf_cek();
$type = $_POST['type'] ?? 'karya';
$id = (int)($_POST['id'] ?? 0);

if ($type === 'karya') {
    $p = satu(q("SELECT gambar FROM projects WHERE id=?", 'i', [$id]));
    if ($p) {
        q("DELETE FROM projects WHERE id=?", 'i', [$id]);
        hapus_gambar($p['gambar']);
        flash("Karya berhasil dihapus.");
    }
    header("Location: " . halaman_utama()); exit;
}

// hapus pengguna
$t = satu(q("SELECT * FROM users WHERE id=?", 'i', [$id]));
if (!$t) { flash("Pengguna tidak ditemukan.", "err"); }
elseif ($id === $u['id']) { flash("Anda tidak dapat menghapus akun sendiri.", "err"); }
elseif ($u['role'] === 'admin' && $t['role'] !== 'siswa') { flash("Admin hanya boleh menghapus data siswa.", "err"); }
else {
    $gambar = baris(q("SELECT gambar FROM projects WHERE user_id=?", 'i', [$id]));
    q("DELETE FROM users WHERE id=?", 'i', [$id]);
    foreach ($gambar as $g) hapus_gambar($g['gambar']);
    flash("Pengguna berhasil dihapus.");
}
header("Location: " . halaman_utama() . "?page=siswa"); exit;
