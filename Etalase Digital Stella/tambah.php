<?php
require 'koneksi.php';
wajib_login();

$u = $_SESSION['user'];
$type = ($_GET['type'] ?? 'karya') === 'user' ? 'user' : 'karya';

if ($type === 'user') wajib_role(['super_admin', 'admin']);

$err = '';

$v = [
    'judul' => '',
    'deskripsi' => '',
    'link_pendukung' => '',
    'link_demo' => '',
    'owner' => $u['id'],
    'nama' => '',
    'username' => '',
    'role' => 'siswa'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();

    foreach ($v as $k => $_) {
        if (isset($_POST[$k])) {
            $v[$k] = trim($_POST[$k]);
        }
    }

    if ($type === 'karya') {
        $owner = $u['id'];

        if (is_admin() && (int)$v['owner'] > 0) {
            $o = satu(q(
                "SELECT id FROM users WHERE id=?",
                'i',
                [(int)$v['owner']]
            ));

            if ($o) {
                $owner = (int)$o['id'];
            }
        }

        if (
            $v['judul'] === '' ||
            $v['deskripsi'] === '' ||
            $v['link_pendukung'] === ''
        ) {
            $err = "Judul, deskripsi, dan link pendukung wajib diisi.";
        } elseif (!url_valid($v['link_pendukung'])) {
            $err = "Link pendukung harus diawali http:// atau https://";
        } elseif (
            $v['link_demo'] !== '' &&
            !url_valid($v['link_demo'])
        ) {
            $err = "Link demo harus diawali http:// atau https://";
        } else {
            $gambar = upload_gambar($_FILES['gambar'] ?? null, $err, true);

            if (!$err) {
                q(
                    "INSERT INTO projects
                    (user_id, judul, deskripsi, gambar, link_pendukung, link_demo)
                    VALUES (?, ?, ?, ?, ?, ?)",
                    'isssss',
                    [
                        $owner,
                        $v['judul'],
                        $v['deskripsi'],
                        $gambar,
                        $v['link_pendukung'],
                        $v['link_demo'] !== '' ? $v['link_demo'] : null
                    ]
                );

                flash("Karya berhasil ditambahkan.");
                header("Location: " . halaman_utama());
                exit;
            }
        }
    } else {
        $pw = $_POST['password'] ?? '';

        $role = $u['role'] === 'super_admin' &&
                in_array($v['role'], ['super_admin', 'admin', 'siswa'])
                ? $v['role']
                : 'siswa';

        if (
            $v['nama'] === '' ||
            $v['username'] === '' ||
            $pw === ''
        ) {
            $err = "Nama, username, dan password wajib diisi.";
        } elseif (
            strlen($v['username']) > 100 ||
            !preg_match('/^[A-Za-z0-9._@-]{4,100}$/', $v['username'])
        ) {
            $err = "Username 4-100 karakter (huruf, angka, titik, @, garis bawah, dan strip).";
        } elseif (strlen($pw) < 6) {
            $err = "Password minimal 6 karakter.";
        } elseif (
            satu(q(
                "SELECT id FROM users WHERE username=?",
                's',
                [$v['username']]
            ))
        ) {
            $err = "Username sudah dipakai.";
        } else {
            q(
                "INSERT INTO users (nama, username, password, role)
                VALUES (?, ?, ?, ?)",
                'ssss',
                [
                    $v['nama'],
                    $v['username'],
                    password_hash($pw, PASSWORD_DEFAULT),
                    $role
                ]
            );

            flash("Pengguna berhasil ditambahkan.");
            header("Location: " . halaman_utama() . "?page=siswa");
            exit;
        }
    }
}

$pemilik = (is_admin() && $type === 'karya')
    ? baris(q(
        "SELECT id, nama, role FROM users
        WHERE role='siswa' OR id=?
        ORDER BY nama",
        'i',
        [$u['id']]
    ))
    : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        Tambah <?= $type === 'karya' ? 'Karya' : 'Pengguna' ?>
        - Etalase Digital Stella
    </title>

    <link rel="stylesheet" href="css/tambah.css">
</head>

<body>

<?php navbar($type === 'karya' ? 'tambah' : 'siswa'); ?>

<main class="wadah sempit">
    <form method="post" enctype="multipart/form-data" class="form-card">

        <h2>
            <?= $type === 'karya'
                ? 'Tambah Karya'
                : ($u['role'] === 'super_admin'
                    ? 'Tambah Pengguna'
                    : 'Tambah Siswa') ?>
        </h2>

        <?php if ($err): ?>
            <div class="alert err"><?= e($err) ?></div>
        <?php endif; ?>

        <?= csrf_field() ?>

        <?php if ($type === 'karya'): ?>

            <label>Judul Projek</label>
            <input
                type="text"
                name="judul"
                value="<?= e($v['judul']) ?>"
                maxlength="150"
                required
            >

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="5" required><?= e($v['deskripsi']) ?></textarea>

            <label>Gambar Karya <small>(JPG/PNG/WEBP, maks. 2 MB)</small></label>
            <input type="file" name="gambar" accept="image/*" required>

            <label>Link Pendukung <small>(repo, dokumentasi, dll.)</small></label>
            <input
                type="url"
                name="link_pendukung"
                value="<?= e($v['link_pendukung']) ?>"
                placeholder="https://"
                required
            >

            <label>Link Demo <small>(opsional)</small></label>
            <input
                type="url"
                name="link_demo"
                value="<?= e($v['link_demo']) ?>"
                placeholder="https://"
            >

            <?php if ($pemilik): ?>
                <label>Pemilik Karya</label>

                <select name="owner">
                    <?php foreach ($pemilik as $o): ?>
                        <option
                            value="<?= (int)$o['id'] ?>"
                            <?= (int)$v['owner'] === (int)$o['id'] ? 'selected' : '' ?>
                        >
                            <?= e($o['nama']) ?>
                            <?= $o['role'] !== 'siswa' ? ' (Saya)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

        <?php else: ?>

            <label>Nama Lengkap</label>
            <input
                type="text"
                name="nama"
                value="<?= e($v['nama']) ?>"
                required
            >

            <label>Username atau Email</label>
            <input
                type="text"
                name="username"
                value="<?= e($v['username']) ?>"
                maxlength="100"
                placeholder="Contoh: Zahra@stella.sch.id"
                required
            >

            <label>Password</label>
            <input type="password" name="password" required>

            <?php if ($u['role'] === 'super_admin'): ?>
                <label>Role</label>

                <select name="role">
                    <?php foreach (['siswa', 'admin', 'super_admin'] as $r): ?>
                        <option
                            value="<?= e($r) ?>"
                            <?= $v['role'] === $r ? 'selected' : '' ?>
                        >
                            <?= e(label_role($r)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

        <?php endif; ?>

        <div class="tombol">
            <button type="submit" class="btn">Simpan</button>

            <a
                class="btn batal"
                href="<?= e(halaman_utama() . ($type === 'user' ? '?page=siswa' : '')) ?>"
            >
                Batal
            </a>
        </div>

    </form>
</main>

</body>
</html>