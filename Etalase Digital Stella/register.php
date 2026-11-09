<?php
require 'koneksi.php';

if (!empty($_SESSION['user'])) {
    header("Location: " . halaman_utama());
    exit;
}

$err = '';
$nama = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if ($nama === '' || $username === '' || $pw === '' || $pw2 === '') {
        $err = "Semua kolom wajib diisi.";
    } elseif (!preg_match('/^[A-Za-z0-9_.@-]{4,100}$/', $username)) {
        $err = "Username 4-100 karakter. Gunakan huruf, angka, @, titik, garis bawah, atau tanda hubung.";
    } elseif (strlen($pw) < 6) {
        $err = "Password minimal 6 karakter.";
    } elseif ($pw !== $pw2) {
        $err = "Konfirmasi password tidak sama.";
    } else {
        $st = $conn->prepare("SELECT id FROM users WHERE username = ?");

        if (!$st) {
            die("Query error: " . $conn->error);
        }

        $st->bind_param("s", $username);
        $st->execute();
        $result = $st->get_result();

        if ($result->num_rows > 0) {
            $err = "Username sudah dipakai.";
            $st->close();
        } else {
            $st->close();

            $hash = password_hash($pw, PASSWORD_DEFAULT);

            $st = $conn->prepare(
                "INSERT INTO users (nama, username, password, role)
                 VALUES (?, ?, ?, 'siswa')"
            );

            if (!$st) {
                die("Query error: " . $conn->error);
            }

            $st->bind_param("sss", $nama, $username, $hash);

            if ($st->execute()) {
                $st->close();
                flash("Pendaftaran berhasil. Silakan login.");
                header("Location: login.php");
                exit;
            } else {
                $err = "Gagal membuat akun: " . $st->error;
                $st->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - Etalase Digital Stella</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
<div class="auth">
    <section class="panel-merah">
        <img src="img/logo-ts2.png" alt="Logo Telkom Schools">
        <h1>Etalase Digital <span>Stella</span></h1>
        <p>Buat akun siswa dan mulai pamerkan projekmu.</p>
    </section>

    <section class="panel-form">
        <form method="post" class="kartu-form">
            <h2>Daftar Akun</h2>
            <p class="sub">Akun baru otomatis berperan sebagai Siswa.</p>

            <?php if ($err): ?>
                <div class="alert err"><?= e($err) ?></div>
            <?php endif; ?>

            <label>Nama Lengkap</label>
            <input type="text" name="nama"
                   value="<?= e($nama) ?>" required>

            <label>Username</label>
            <input type="text" name="username"
                   value="<?= e($username) ?>" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Konfirmasi Password</label>
            <input type="password" name="password2" required>

            <button type="submit">Daftar</button>

            <p class="alih">
                Sudah punya akun? <a href="login.php">Login</a>
            </p>
        </form>
    </section>
</div>
</body>
</html>