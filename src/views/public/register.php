<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];

    if ($role == 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    } elseif ($role == 'seller') {
        header("Location: ../seller/dashboard.php");
        exit;
    } elseif ($role == 'courier' || $role == 'petugas' || $role == 'kurir') {
        header("Location: ../seller/petugas-dashboard.php");
        exit;
    } else {
        header("Location: home.php");
        exit;
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($name == "" || $email == "" || $password == "") {
        $error = "Nama, email, dan password wajib diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        $check = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id
            FROM users
            WHERE email='$email'
            LIMIT 1
        "));

        if ($check) {
            $error = "Email sudah digunakan.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query($conn, "
                INSERT INTO users (
                    name,
                    email,
                    password,
                    role,
                    status,
                    phone,
                    address
                ) VALUES (
                    '$name',
                    '$email',
                    '$hash',
                    'buyer',
                    'active',
                    '$phone',
                    '$address'
                )
            ");

            $user_id = mysqli_insert_id($conn);

            mysqli_query($conn, "
                INSERT INTO notifications (
                    user_id,
                    title,
                    message
                ) VALUES (
                    '$user_id',
                    'Registrasi Berhasil',
                    'Akun pelanggan kamu berhasil dibuat. Silakan buat pesanan laundry pertama kamu.'
                )
            ");

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Daftar Pelanggan - Laundry Express</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/buyer-navbar.php"; ?>

<section class="section-wrap" style="padding-top:55px;">
    <div style="max-width:1040px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:30px;align-items:center;">

        <div>
            <p style="font-weight:900;color:#0284c7;margin-bottom:10px;">
                Daftar Pelanggan
            </p>

            <h1 class="page-title" style="font-size:54px;">
                Buat Akun untuk Pesan Laundry
            </h1>

            <p class="page-subtitle" style="line-height:1.8;font-size:17px;">
                Register hanya tersedia untuk pelanggan. Akun mitra dan kurir dibuat langsung oleh admin.
            </p>

            <div class="modern-card" style="padding:26px;margin-top:28px;">
                <h3 style="font-size:24px;font-weight:900;color:#0369a1;margin-bottom:12px;">
                    Kenapa daftar?
                </h3>

                <p style="color:#64748b;line-height:1.8;">
                    Kamu bisa memilih mitra laundry, memilih delivery, memantau status cucian, dan melihat total harga otomatis setelah mitra mengecek berat cucian.
                </p>
            </div>
        </div>

        <div class="modern-card" style="padding:34px;">
            <h2 style="font-size:32px;font-weight:900;color:#0369a1;margin-bottom:10px;">
                Register
            </h2>

            <p style="color:#64748b;margin-bottom:24px;">
                Isi data pelanggan dengan benar.
            </p>

            <?php if ($error != "") : ?>
                <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 16px;font-weight:800;margin-bottom:20px;">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div style="margin-bottom:16px;">
                    <label style="font-weight:900;color:#0369a1;margin-bottom:9px;display:block;">Nama Lengkap</label>
                    <input type="text" name="name" class="modern-input" placeholder="Nama pelanggan" required>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:900;color:#0369a1;margin-bottom:9px;display:block;">Email</label>
                    <input type="email" name="email" class="modern-input" placeholder="email@gmail.com" required>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:900;color:#0369a1;margin-bottom:9px;display:block;">Nomor WhatsApp</label>
                    <input type="text" name="phone" class="modern-input" placeholder="08xxxxxxxxxx">
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:900;color:#0369a1;margin-bottom:9px;display:block;">Alamat</label>
                    <textarea name="address" class="modern-input" rows="3" placeholder="Alamat lengkap pelanggan"></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;">
                    <div>
                        <label style="font-weight:900;color:#0369a1;margin-bottom:9px;display:block;">Password</label>
                        <input type="password" name="password" class="modern-input" placeholder="Password" required>
                    </div>

                    <div>
                        <label style="font-weight:900;color:#0369a1;margin-bottom:9px;display:block;">Konfirmasi</label>
                        <input type="password" name="confirm_password" class="modern-input" placeholder="Ulangi password" required>
                    </div>
                </div>

                <button type="submit" class="modern-btn" style="width:100%;">
                    Daftar Sebagai Pelanggan
                </button>

                <p style="text-align:center;margin-top:18px;color:#64748b;">
                    Sudah punya akun?
                    <a href="login.php" style="font-weight:900;color:#0284c7;">Login di sini</a>
                </p>
            </form>
        </div>

    </div>
</section>

<style>
@media (max-width: 900px) {
    .section-wrap > div,
    form div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>