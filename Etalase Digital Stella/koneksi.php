<?php
// koneksi.php - koneksi database + fungsi bantu (session, hak akses, upload, tampilan bersama)
if (session_status() === PHP_SESSION_NONE) session_start();
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect("localhost", "root", "", "db_etalase_stella");
if (!$conn) {
    die("Koneksi database gagal. Pastikan MySQL aktif dan database <b>db_etalase_stella</b> sudah diimport dari folder database.");
}
mysqli_set_charset($conn, "utf8mb4");

/* ---------- Query bantu (prepared statement) ---------- */
function q($sql, $types = '', $params = []) {
    global $conn;
    $st = mysqli_prepare($conn, $sql);
    if (!$st) die("Query error: " . htmlspecialchars(mysqli_error($conn)));
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    if (!mysqli_stmt_execute($st)) return false;
    $r = mysqli_stmt_get_result($st);
    return $r ? $r : $st;
}
function baris($r) { return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : []; }
function satu($r)  { return $r ? (mysqli_fetch_assoc($r) ?: null) : null; }

/* ---------- Umum ---------- */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function label_role($r) { return ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'siswa' => 'Siswa'][$r] ?? $r; }
function url_valid($u) { return (bool)preg_match('~^https?://~i', $u) && filter_var($u, FILTER_VALIDATE_URL); }
function flash($pesan, $tipe = 'ok') { $_SESSION['flash'] = [$pesan, $tipe]; }
function tampil_flash() {
    if (!empty($_SESSION['flash'])) {
        echo '<div class="alert ' . e($_SESSION['flash'][1]) . '">' . e($_SESSION['flash'][0]) . '</div>';
        unset($_SESSION['flash']);
    }
}
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_cek() {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) die("Token tidak valid. Muat ulang halaman.");
}

/* ---------- Hak akses ---------- */
function halaman_utama() {
    return (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'], ['super_admin', 'admin'])) ? 'dashboard_admin.php' : 'dashboard_user.php';
}
function wajib_login() { if (empty($_SESSION['user'])) { header("Location: login.php"); exit; } }
function wajib_role($roles) {
    wajib_login();
    if (!in_array($_SESSION['user']['role'], $roles)) { header("Location: " . halaman_utama()); exit; }
}
function is_admin() { return !empty($_SESSION['user']) && in_array($_SESSION['user']['role'], ['super_admin', 'admin']); }

/* ---------- Upload gambar ---------- */
function upload_gambar($f, &$err, $wajib = false) {
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) { if ($wajib) $err = "Gambar karya wajib diunggah."; return null; }
    if ($f['error'] !== UPLOAD_ERR_OK) { $err = "Upload gambar gagal."; return null; }
    if ($f['size'] > 2 * 1024 * 1024) { $err = "Ukuran gambar maksimal 2 MB."; return null; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || !@getimagesize($f['tmp_name'])) { $err = "File harus berupa gambar JPG, PNG, atau WEBP."; return null; }
    $nama = bin2hex(random_bytes(8)) . "." . $ext;
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . "/img/karya/" . $nama)) { $err = "Gagal menyimpan gambar."; return null; }
    return $nama;
}
function hapus_gambar($n) { if ($n && is_file(__DIR__ . "/img/karya/" . $n)) @unlink(__DIR__ . "/img/karya/" . $n); }

/* ---------- Tampilan bersama ---------- */
function navbar($aktif = 'karya') {
    $u = $_SESSION['user'];
    $home = halaman_utama();
    $menu = ['karya' => ['Katalog Karya', $home]];
    if (is_admin()) $menu['siswa'] = [$u['role'] === 'super_admin' ? 'Kelola Pengguna' : 'Kelola Siswa', $home . '?page=siswa'];
    $menu['tambah'] = ['Tambah Karya', 'tambah.php'];
    echo '<header class="topbar"><a class="brand" href="' . $home . '"><img src="img/logo-ts2.png" alt="Telkom Schools"><span>Etalase Digital <b>Stella</b></span></a><nav>';
    foreach ($menu as $k => $m) echo '<a href="' . $m[1] . '" class="' . ($aktif === $k ? 'aktif' : '') . '">' . $m[0] . '</a>';
    echo '</nav><div class="akun"><span>' . e($u['nama']) . '<small>' . label_role($u['role']) . '</small></span><a class="btn-keluar" href="logout.php">Keluar</a></div></header>';
}

function kartu($p, $aksi = false) {
    $img = !empty($p['gambar']) ? 'img/karya/' . rawurlencode($p['gambar']) : 'img/gd' . (($p['id'] % 4) + 1) . '.jpeg';
    $desk = mb_strimwidth($p['deskripsi'], 0, 110, '…', 'UTF-8');
    echo '<article class="kartu"><img src="' . e($img) . '" alt="' . e($p['judul']) . '"><div class="kartu-isi">';
    echo '<h3>' . e($p['judul']) . '</h3><p class="penulis">oleh ' . e($p['penulis']) . ' · ' . date('d M Y', strtotime($p['created_at'])) . '</p>';
    echo '<p>' . e($desk) . '</p><div class="tautan"><a href="' . e($p['link_pendukung']) . '" target="_blank" rel="noopener">Link Pendukung</a>';
    if (!empty($p['link_demo'])) echo '<a class="demo" href="' . e($p['link_demo']) . '" target="_blank" rel="noopener">Demo</a>';
    echo '</div>';
    if ($aksi) {
        echo '<div class="aksi"><a class="btn kecil" href="edit.php?id=' . (int)$p['id'] . '">Edit</a>';
        echo '<form method="post" action="delete.php" onsubmit="return confirm(\'Hapus karya ini?\')">' . csrf_field();
        echo '<input type="hidden" name="type" value="karya"><input type="hidden" name="id" value="' . (int)$p['id'] . '"><button class="btn kecil hapus">Hapus</button></form></div>';
    }
    echo '</div></article>';
}

function kotak_cari($q, $page = '') {
    echo '<form class="cari" method="get">';
    if ($page) echo '<input type="hidden" name="page" value="' . e($page) . '">';
    echo '<input type="search" name="q" value="' . e($q) . '" placeholder="Cari karya siswa (judul, deskripsi, atau nama siswa)..."><button>Cari</button></form>';
}
