<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

$message = "";
$error = "";

$mitras = mysqli_query($conn, "
    SELECT id, mitra_name
    FROM laundry_mitras
    WHERE status='active'
    ORDER BY mitra_name ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $mitra_id = (int) $_POST['mitra_id'];
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id FROM users WHERE email='$email' LIMIT 1
        "));

        if ($check) {
            $error = "Email petugas sudah digunakan.";
        } else {
            mysqli_query($conn, "
                INSERT INTO users(name,email,password,role,mitra_id,status,phone,address)
                VALUES('$fullname','$email','$password','petugas','$mitra_id','active','$phone','$address')
            ");

            $staff_user_id = mysqli_insert_id($conn);

            mysqli_query($conn, "
                INSERT INTO staff(mitra_id,user_id,fullname,phone,address,status)
                VALUES('$mitra_id','$staff_user_id','$fullname','$phone','$address','active')
            ");

            $message = "Petugas berhasil ditambahkan.";
        }
    }

    if ($action === 'update') {
        $staff_id = (int) $_POST['staff_id'];
        $staff_user_id = (int) $_POST['user_id'];
        $mitra_id = (int) $_POST['mitra_id'];

        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $staff_status = mysqli_real_escape_string($conn, $_POST['status']);

        $user_status = $staff_status === 'active' ? 'active' : 'blocked';

        mysqli_query($conn, "
            UPDATE users
            SET name='$fullname',
                email='$email',
                phone='$phone',
                address='$address',
                mitra_id='$mitra_id',
                status='$user_status'
            WHERE id='$staff_user_id'
            AND role='petugas'
        ");

        mysqli_query($conn, "
            UPDATE staff
            SET mitra_id='$mitra_id',
                fullname='$fullname',
                phone='$phone',
                address='$address',
                status='$staff_status'
            WHERE id='$staff_id'
        ");

        $message = "Data petugas berhasil diperbarui.";
    }

    if ($action === 'reset_password') {
        $staff_user_id = (int) $_POST['user_id'];
        $newPassword = password_hash("123456", PASSWORD_DEFAULT);

        mysqli_query($conn, "
            UPDATE users
            SET password='$newPassword'
            WHERE id='$staff_user_id'
            AND role='petugas'
        ");

        $message = "Password petugas berhasil direset menjadi 123456.";
    }
}

$staffs = mysqli_query($conn, "
    SELECT
        staff.*,
        users.email,
        users.status AS user_status,
        laundry_mitras.mitra_name
    FROM staff
    JOIN users ON staff.user_id = users.id
    LEFT JOIN laundry_mitras ON staff.mitra_id = laundry_mitras.id
    ORDER BY staff.id DESC
");

function staffBadgeAdmin($status)
{
    if ($status === 'active') {
        return "<span class='status-pill' style='background:#dcfce7;color:#166534;'>Aktif</span>";
    }

    return "<span class='status-pill' style='background:#fee2e2;color:#b91c1c;'>Nonaktif</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Laundry UMKM browser icon -->
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg?v=7">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon-32x32.png?v=7">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon-16x16.png?v=7">
    <link rel="shortcut icon" href="../../assets/images/favicon.ico?v=7">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/apple-touch-icon.png?v=7">
    <link rel="manifest" href="../../assets/images/site.webmanifest?v=7">
    <meta name="theme-color" content="#0ea5e9">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Kelola Petugas</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="soft-bg-pattern admin-panel-page">

<?php include "../layouts/admin-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/admin-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="margin-bottom:22px;">
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Admin Panel</p>
            <h1 class="page-title">Kelola Petugas</h1>
            <p class="page-subtitle">Admin dapat mengelola seluruh petugas dari semua seller.</p>
        </div>

        <?php if ($message !== "") : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:20px;">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== "") : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:20px;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <details class="modern-card" style="padding:20px;margin-bottom:22px;">
            <summary style="cursor:pointer;font-weight:800;color:#0369a1;font-size:16px;">
                Tambah Petugas Baru
            </summary>

            <form method="POST" style="margin-top:20px;">
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Seller</label>
                        <select name="mitra_id" class="modern-input" required>
                            <option value="">Pilih seller</option>
                            <?php
                            mysqli_data_seek($mitras, 0);
                            while ($mitra = mysqli_fetch_assoc($mitras)) :
                            ?>
                                <option value="<?= $mitra['id']; ?>">
                                    <?= htmlspecialchars($mitra['mitra_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Petugas</label>
                        <input type="text" name="fullname" class="modern-input" required>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Email</label>
                        <input type="email" name="email" class="modern-input" required>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Telepon</label>
                        <input type="text" name="phone" class="modern-input">
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Password</label>
                        <input type="password" name="password" class="modern-input" value="123456" required>
                    </div>

                    <div style="grid-column:1/-1;">
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat</label>
                        <textarea name="address" rows="3" class="modern-input"></textarea>
                    </div>
                </div>

                <button type="submit" class="modern-btn" style="margin-top:16px;">
                    Simpan Petugas
                </button>
            </form>
        </details>

        <div style="display:flex;flex-direction:column;gap:18px;">

            <?php if ($staffs && mysqli_num_rows($staffs) > 0) : ?>

                <?php while ($row = mysqli_fetch_assoc($staffs)) : ?>

                    <div class="modern-card" style="padding:22px;">
                        <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px;">
                            <div>
                                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                    Petugas #<?= $row['id']; ?>
                                </p>

                                <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">
                                    <?= htmlspecialchars($row['fullname']); ?>
                                </h2>

                                <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                    <?= htmlspecialchars($row['email']); ?> • <?= htmlspecialchars($row['mitra_name'] ?: '-'); ?>
                                </p>
                            </div>

                            <?= staffBadgeAdmin($row['status']); ?>
                        </div>

                        <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                            <summary style="cursor:pointer;font-weight:800;color:#0369a1;">
                                Edit Petugas
                            </summary>

                            <form method="POST" style="margin-top:16px;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="staff_id" value="<?= $row['id']; ?>">
                                <input type="hidden" name="user_id" value="<?= $row['user_id']; ?>">

                                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Seller</label>
                                        <select name="mitra_id" class="modern-input" required>
                                            <?php
                                            mysqli_data_seek($mitras, 0);
                                            while ($mitra = mysqli_fetch_assoc($mitras)) :
                                            ?>
                                                <option value="<?= $mitra['id']; ?>" <?= $row['mitra_id'] == $mitra['id'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($mitra['mitra_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama</label>
                                        <input type="text" name="fullname" class="modern-input" value="<?= htmlspecialchars($row['fullname']); ?>" required>
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Email</label>
                                        <input type="email" name="email" class="modern-input" value="<?= htmlspecialchars($row['email']); ?>" required>
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Telepon</label>
                                        <input type="text" name="phone" class="modern-input" value="<?= htmlspecialchars($row['phone']); ?>">
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Status</label>
                                        <select name="status" class="modern-input">
                                            <option value="active" <?= $row['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="inactive" <?= $row['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                        </select>
                                    </div>

                                    <div style="grid-column:1/-1;">
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat</label>
                                        <textarea name="address" rows="3" class="modern-input"><?= htmlspecialchars($row['address']); ?></textarea>
                                    </div>
                                </div>

                                <button type="submit" class="modern-btn" style="margin-top:15px;">
                                    Simpan Perubahan
                                </button>
                            </form>

                            <form method="POST" style="margin-top:12px;" onsubmit="return confirm('Reset password petugas menjadi 123456?')">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= $row['user_id']; ?>">

                                <button type="submit" class="modern-btn-outline">
                                    Reset Password
                                </button>
                            </form>
                        </details>
                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="modern-card" style="padding:36px;text-align:center;">
                    <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                        Belum Ada Petugas
                    </h2>

                    <p style="color:#64748b;">
                        Tambahkan petugas untuk seller.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<style>
@media (max-width: 800px) {
    form div[style*="grid-template-columns:repeat(2,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>