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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $mitra_name = mysqli_real_escape_string($conn, $_POST['mitra_name']);
        $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $pickup_fee = (int) $_POST['pickup_fee'];
        $delivery_fee = (int) $_POST['delivery_fee'];

        $check = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id FROM users WHERE email='$email' LIMIT 1
        "));

        if ($check) {
            $error = "Email seller sudah digunakan.";
        } else {
            mysqli_query($conn, "
                INSERT INTO users(name,email,password,role,status,phone,address)
                VALUES('$name','$email','$password','mitra','active','$phone','$address')
            ");

            $user_id = mysqli_insert_id($conn);

            mysqli_query($conn, "
                INSERT INTO laundry_mitras(
                    user_id,mitra_name,owner_name,phone,city,address,description,pickup_fee,delivery_fee,status
                )
                VALUES(
                    '$user_id','$mitra_name','$owner_name','$phone','$city','$address','$description','$pickup_fee','$delivery_fee','active'
                )
            ");

            $message = "Seller berhasil ditambahkan.";
        }
    }

    if ($action === 'update') {
        $mitra_id = (int) $_POST['mitra_id'];
        $user_id = (int) $_POST['user_id'];

        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);

        $mitra_name = mysqli_real_escape_string($conn, $_POST['mitra_name']);
        $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $pickup_fee = (int) $_POST['pickup_fee'];
        $delivery_fee = (int) $_POST['delivery_fee'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $user_status = $status === 'active' ? 'active' : 'blocked';

        mysqli_query($conn, "
            UPDATE users
            SET name='$name',
                email='$email',
                phone='$phone',
                address='$address',
                status='$user_status'
            WHERE id='$user_id'
            AND role='mitra'
        ");

        mysqli_query($conn, "
            UPDATE laundry_mitras
            SET mitra_name='$mitra_name',
                owner_name='$owner_name',
                phone='$phone',
                city='$city',
                address='$address',
                description='$description',
                pickup_fee='$pickup_fee',
                delivery_fee='$delivery_fee',
                status='$status'
            WHERE id='$mitra_id'
        ");

        $message = "Data seller berhasil diperbarui.";
    }

    if ($action === 'reset_password') {
        $user_id = (int) $_POST['user_id'];
        $newPassword = password_hash("123456", PASSWORD_DEFAULT);

        mysqli_query($conn, "
            UPDATE users
            SET password='$newPassword'
            WHERE id='$user_id'
            AND role='mitra'
        ");

        $message = "Password seller berhasil direset menjadi 123456.";
    }
}

$partners = mysqli_query($conn, "
    SELECT 
        laundry_mitras.*,
        users.name,
        users.email,
        users.status AS user_status
    FROM laundry_mitras
    JOIN users ON laundry_mitras.user_id = users.id
    ORDER BY laundry_mitras.id DESC
");

function mitraBadge($status)
{
    if ($status === 'active') {
        return "<span class='status-pill' style='background:#dcfce7;color:#166534;'>Aktif</span>";
    }

    if ($status === 'pending') {
        return "<span class='status-pill' style='background:#fef3c7;color:#92400e;'>Pending</span>";
    }

    return "<span class='status-pill' style='background:#fee2e2;color:#b91c1c;'>Diblokir</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Seller</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/admin-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/admin-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="margin-bottom:22px;">
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                Admin Panel
            </p>

            <h1 class="page-title">
                Kelola Seller
            </h1>

            <p class="page-subtitle">
                Tambah, edit, blokir, dan reset password seller laundry.
            </p>
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
                Tambah Seller Baru
            </summary>

            <form method="POST" style="margin-top:20px;">
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Akun</label>
                        <input type="text" name="name" class="modern-input" required>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Email</label>
                        <input type="email" name="email" class="modern-input" required>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Password</label>
                        <input type="password" name="password" class="modern-input" value="123456" required>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Seller</label>
                        <input type="text" name="mitra_name" class="modern-input" required>
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Pemilik</label>
                        <input type="text" name="owner_name" class="modern-input">
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Telepon</label>
                        <input type="text" name="phone" class="modern-input">
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Kota</label>
                        <input type="text" name="city" class="modern-input">
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Biaya Jemput</label>
                        <input type="number" name="pickup_fee" class="modern-input" value="0">
                    </div>

                    <div>
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Biaya Antar</label>
                        <input type="number" name="delivery_fee" class="modern-input" value="0">
                    </div>

                    <div style="grid-column:1/-1;">
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat</label>
                        <textarea name="address" rows="3" class="modern-input"></textarea>
                    </div>

                    <div style="grid-column:1/-1;">
                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Deskripsi</label>
                        <textarea name="description" rows="3" class="modern-input"></textarea>
                    </div>
                </div>

                <button type="submit" class="modern-btn" style="margin-top:16px;">
                    Simpan Seller
                </button>
            </form>
        </details>

        <div style="display:flex;flex-direction:column;gap:18px;">

            <?php if ($partners && mysqli_num_rows($partners) > 0) : ?>

                <?php while ($row = mysqli_fetch_assoc($partners)) : ?>

                    <div class="modern-card" style="padding:22px;">
                        <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px;">
                            <div>
                                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                    Seller #<?= $row['id']; ?>
                                </p>

                                <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">
                                    <?= htmlspecialchars($row['mitra_name']); ?>
                                </h2>

                                <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                    <?= htmlspecialchars($row['email']); ?> • <?= htmlspecialchars($row['phone'] ?: '-'); ?>
                                </p>
                            </div>

                            <?= mitraBadge($row['status']); ?>
                        </div>

                        <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                            <summary style="cursor:pointer;font-weight:800;color:#0369a1;">
                                Edit Seller
                            </summary>

                            <form method="POST" style="margin-top:16px;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="mitra_id" value="<?= $row['id']; ?>">
                                <input type="hidden" name="user_id" value="<?= $row['user_id']; ?>">

                                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Akun</label>
                                        <input type="text" name="name" class="modern-input" value="<?= htmlspecialchars($row['name']); ?>" required>
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Email</label>
                                        <input type="email" name="email" class="modern-input" value="<?= htmlspecialchars($row['email']); ?>" required>
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Seller</label>
                                        <input type="text" name="mitra_name" class="modern-input" value="<?= htmlspecialchars($row['mitra_name']); ?>" required>
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Pemilik</label>
                                        <input type="text" name="owner_name" class="modern-input" value="<?= htmlspecialchars($row['owner_name']); ?>">
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Telepon</label>
                                        <input type="text" name="phone" class="modern-input" value="<?= htmlspecialchars($row['phone']); ?>">
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Kota</label>
                                        <input type="text" name="city" class="modern-input" value="<?= htmlspecialchars($row['city']); ?>">
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Biaya Jemput</label>
                                        <input type="number" name="pickup_fee" class="modern-input" value="<?= htmlspecialchars($row['pickup_fee']); ?>">
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Biaya Antar</label>
                                        <input type="number" name="delivery_fee" class="modern-input" value="<?= htmlspecialchars($row['delivery_fee']); ?>">
                                    </div>

                                    <div>
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Status</label>
                                        <select name="status" class="modern-input">
                                            <option value="active" <?= $row['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="blocked" <?= $row['status'] === 'blocked' ? 'selected' : ''; ?>>Blokir</option>
                                        </select>
                                    </div>

                                    <div style="grid-column:1/-1;">
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat</label>
                                        <textarea name="address" rows="3" class="modern-input"><?= htmlspecialchars($row['address']); ?></textarea>
                                    </div>

                                    <div style="grid-column:1/-1;">
                                        <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Deskripsi</label>
                                        <textarea name="description" rows="3" class="modern-input"><?= htmlspecialchars($row['description']); ?></textarea>
                                    </div>
                                </div>

                                <button type="submit" class="modern-btn" style="margin-top:15px;">
                                    Simpan Perubahan
                                </button>
                            </form>

                            <form method="POST" style="margin-top:12px;" onsubmit="return confirm('Reset password seller menjadi 123456?')">
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
                        Belum Ada Seller
                    </h2>

                    <p style="color:#64748b;">
                        Tambahkan seller laundry dari tombol di atas.
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