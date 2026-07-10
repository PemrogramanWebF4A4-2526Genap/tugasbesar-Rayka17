<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

function redirectByRole($role)
{
    if ($role === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    }

    if ($role === 'mitra' || $role === 'seller') {
        header("Location: ../seller/dashboard.php");
        exit;
    }

    if ($role === 'petugas') {
        header("Location: ../seller/petugas-dashboard.php");
        exit;
    }

    if ($role === 'customer_service') {
        header("Location: ../customer_service/dashboard.php");
        exit;
    }

    if ($role === 'buyer') {
        header("Location: ../buyer/orders.php");
        exit;
    }

    header("Location: home.php");
    exit;
}

if (isset($_SESSION['user'])) {
    $id = (int) $_SESSION['user']['id'];

    $freshUser = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM users
        WHERE id='$id'
        LIMIT 1
    "));

    if (!$freshUser || $freshUser['status'] !== 'active') {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $_SESSION['user'] = $freshUser;
    redirectByRole($freshUser['role']);
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM users
        WHERE email='$email'
        LIMIT 1
    "));

    if (!$user) {
        $error = "Email atau password salah.";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Email atau password salah.";
    } elseif ($user['status'] !== 'active') {
        $error = "Akun belum aktif atau sedang diblokir.";
    } else {
        $_SESSION['user'] = $user;
        redirectByRole($user['role']);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Laundry UMKM</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/buyer-navbar.php"; ?>

<section class="auth-wrap">
    <div class="auth-grid">

        <div>
            <p style="font-weight:800;color:#0284c7;margin-bottom:10px;">
                Laundry UMKM
            </p>

            <h1 class="auth-title">
                Masuk ke Sistem Laundry
            </h1>

            <p style="color:#64748b;line-height:1.8;max-width:420px;">
                Login sebagai admin, seller, petugas, customer service, atau pelanggan.
            </p>
        </div>

        <div class="modern-card auth-card">
            <h2>Login</h2>

            <p style="color:#64748b;margin-bottom:22px;">
                Masukkan email dan password.
            </p>

            <?php if ($error !== "") : ?>
                <div style="background:#fee2e2;color:#b91c1c;border-radius:16px;padding:13px 15px;font-weight:800;margin-bottom:18px;">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div style="margin-bottom:16px;">
                    <label style="font-weight:800;color:#0369a1;margin-bottom:8px;display:block;">
                        Email
                    </label>
                    <input type="email" name="email" class="modern-input" required>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-weight:800;color:#0369a1;margin-bottom:8px;display:block;">
                        Password
                    </label>
                    <input type="password" name="password" class="modern-input" required>
                </div>

                <button type="submit" class="modern-btn" style="width:100%;">
                    Masuk
                </button>

                <p style="text-align:center;margin-top:17px;color:#64748b;">
                    Belum punya akun?
                    <a href="register.php" style="font-weight:800;color:#0284c7;">
                        Daftar pelanggan
                    </a>
                </p>

            </form>
        </div>

    </div>
</section>

<script src="../../assets/js/modern.js"></script>

</body>
</html>