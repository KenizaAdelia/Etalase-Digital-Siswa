<?php
require 'koneksi.php';
if (!empty($_SESSION['user'])) { header("Location: " . halaman_utama()); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = satu(q("SELECT * FROM users WHERE username=?", 's', [$username]));
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'nama' => $user['nama'], 'username' => $user['username'], 'role' => $user['role']];
        header("Location: " . halaman_utama()); exit;
    }
    $err = "Username atau password salah.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Etalase Digital Stella</title>
<link rel="stylesheet" href="css/login.css">
</head>
<body>
<div class="auth">
  <section class="panel-merah">
    <img src="img/logo-ts2.png" alt="Logo Telkom Schools">
    <h1>Etalase Digital <span>Stella</span></h1>
    <p>Tempat siswa memamerkan karya digital terbaiknya.</p>
  </section>
  <section class="panel-form">
    <form method="post" class="kartu-form">
      <h2>Masuk</h2>
      <p class="sub">Silakan login untuk melanjutkan.</p>
      <?php tampil_flash(); if ($err) echo '<div class="alert err">' . e($err) . '</div>'; ?>
      <label>Username</label><input type="text" name="username" required autofocus>
      <label>Password</label><input type="password" name="password" required>
      <button type="submit">Login</button>
      <p class="alih">Belum punya akun? <a href="register.php">Daftar sebagai siswa</a></p>
    </form>
  </section>
</div>
</body>
</html>
