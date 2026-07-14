<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['mitra', 'seller'])) {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$error = "";

$mitra = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM laundry_mitras
    WHERE user_id='$user_id'
    LIMIT 1
"));

$mitra_id = $mitra['id'] ?? 0;

if (!$mitra) {
    $error = "Akun seller belum terhubung ke data laundry_mitras.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mitra) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $service_name = mysqli_real_escape_string($conn, $_POST['service_name'] ?? '');
        $service_category = mysqli_real_escape_string($conn, $_POST['service_category'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $price_per_kg = (int) ($_POST['price_per_kg'] ?? 0);
        $unit = mysqli_real_escape_string($conn, $_POST['unit'] ?? 'kg');
        $estimated_time = mysqli_real_escape_string($conn, $_POST['estimated_time'] ?? '');

        if ($service_name === '' || $price_per_kg <= 0) {
            header("Location: services.php?error=1");
            exit;
        }

        mysqli_query($conn, "
            INSERT INTO laundry_services(
                mitra_id,
                service_name,
                service_category,
                price_per_kg,
                unit,
                estimated_time,
                description,
                status
            )
            VALUES(
                '$mitra_id',
                '$service_name',
                '$service_category',
                '$price_per_kg',
                '$unit',
                '$estimated_time',
                '$description',
                'active'
            )
        ");

        header("Location: services.php?created=1");
        exit;
    }

    if ($action === 'update') {
        $service_id = (int) ($_POST['service_id'] ?? 0);

        $service_name = mysqli_real_escape_string($conn, $_POST['service_name'] ?? '');
        $service_category = mysqli_real_escape_string($conn, $_POST['service_category'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $price_per_kg = (int) ($_POST['price_per_kg'] ?? 0);
        $unit = mysqli_real_escape_string($conn, $_POST['unit'] ?? 'kg');
        $estimated_time = mysqli_real_escape_string($conn, $_POST['estimated_time'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active');

        $allowedStatus = ['active', 'inactive'];

        if ($service_id <= 0 || $service_name === '' || $price_per_kg <= 0 || !in_array($status, $allowedStatus)) {
            header("Location: services.php?error=1");
            exit;
        }

        mysqli_query($conn, "
            UPDATE laundry_services
            SET
                service_name='$service_name',
                service_category='$service_category',
                price_per_kg='$price_per_kg',
                unit='$unit',
                estimated_time='$estimated_time',
                description='$description',
                status='$status'
            WHERE id='$service_id'
            AND mitra_id='$mitra_id'
        ");

        header("Location: services.php?updated=1");
        exit;
    }

    if ($action === 'delete') {
        $service_id = (int) ($_POST['service_id'] ?? 0);

        mysqli_query($conn, "
            UPDATE laundry_services
            SET status='inactive'
            WHERE id='$service_id'
            AND mitra_id='$mitra_id'
        ");

        header("Location: services.php?deleted=1");
        exit;
    }

    header("Location: services.php?error=1");
    exit;
}

$services = mysqli_query($conn, "
    SELECT *
    FROM laundry_services
    WHERE mitra_id='$mitra_id'
    ORDER BY id DESC
");

function serviceBadge($status)
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Kelola Layanan</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="soft-bg-pattern seller-panel-page">

<?php include "../layouts/seller-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/seller-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="margin-bottom:22px;">
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Seller Panel</p>
            <h1 class="page-title">Kelola Layanan</h1>
            <p class="page-subtitle">Tambah, edit, dan nonaktifkan layanan laundry milik seller.</p>
        </div>

        <?php if (isset($_GET['created'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:20px;">
                Layanan berhasil ditambahkan.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:20px;">
                Layanan berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:20px;">
                Layanan berhasil dinonaktifkan.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) || $error !== "") : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:20px;">
                <?= $error !== "" ? htmlspecialchars($error) : "Gagal memproses layanan."; ?>
            </div>
        <?php endif; ?>

        <?php if ($mitra) : ?>

            <details class="modern-card" style="padding:20px;margin-bottom:22px;" open>
                <summary style="cursor:pointer;font-weight:800;color:#0369a1;font-size:16px;">
                    Tambah Layanan Baru
                </summary>

                <form method="POST" style="margin-top:20px;">
                    <input type="hidden" name="action" value="create">

                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                        <div>
                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Layanan</label>
                            <input type="text" name="service_name" class="modern-input" placeholder="Cuci Setrika Reguler" required>
                        </div>

                        <div>
                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Kategori</label>
                            <input type="text" name="service_category" class="modern-input" placeholder="Kiloan / Satuan / Express">
                        </div>

                        <div>
                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Harga</label>
                            <input type="number" name="price_per_kg" class="modern-input" placeholder="7000" required>
                        </div>

                        <div>
                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Satuan</label>
                            <input type="text" name="unit" class="modern-input" value="kg" required>
                        </div>

                        <div>
                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Estimasi</label>
                            <input type="text" name="estimated_time" class="modern-input" placeholder="2 hari">
                        </div>

                        <div style="grid-column:1/-1;">
                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Deskripsi</label>
                            <textarea name="description" rows="3" class="modern-input" placeholder="Deskripsi layanan laundry"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="modern-btn" style="margin-top:16px;">
                        Simpan Layanan
                    </button>
                </form>
            </details>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;">

                <?php if ($services && mysqli_num_rows($services) > 0) : ?>

                    <?php while ($row = mysqli_fetch_assoc($services)) : ?>

                        <div class="modern-card" style="padding:22px;">
                            <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px;">
                                <div>
                                    <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                        Layanan #<?= $row['id']; ?>
                                    </p>

                                    <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">
                                        <?= htmlspecialchars($row['service_name']); ?>
                                    </h2>

                                    <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                        <?= htmlspecialchars($row['service_category'] ?: '-'); ?> • Estimasi <?= htmlspecialchars($row['estimated_time'] ?: '-'); ?>
                                    </p>
                                </div>

                                <?= serviceBadge($row['status']); ?>
                            </div>

                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;margin-bottom:14px;">
                                <p style="color:#64748b;font-weight:700;font-size:12px;">Harga</p>

                                <h3 style="font-size:22px;font-weight:900;color:#0369a1;margin-top:5px;">
                                    Rp <?= number_format($row['price_per_kg'], 0, ',', '.'); ?>
                                    <span style="font-size:13px;color:#64748b;">/ <?= htmlspecialchars($row['unit']); ?></span>
                                </h3>
                            </div>

                            <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                                <summary style="cursor:pointer;font-weight:800;color:#0369a1;">
                                    Edit Layanan
                                </summary>

                                <form method="POST" style="margin-top:16px;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="service_id" value="<?= $row['id']; ?>">

                                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                                        <div>
                                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Layanan</label>
                                            <input type="text" name="service_name" class="modern-input" value="<?= htmlspecialchars($row['service_name']); ?>" required>
                                        </div>

                                        <div>
                                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Kategori</label>
                                            <input type="text" name="service_category" class="modern-input" value="<?= htmlspecialchars($row['service_category']); ?>">
                                        </div>

                                        <div>
                                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Harga</label>
                                            <input type="number" name="price_per_kg" class="modern-input" value="<?= htmlspecialchars($row['price_per_kg']); ?>" required>
                                        </div>

                                        <div>
                                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Satuan</label>
                                            <input type="text" name="unit" class="modern-input" value="<?= htmlspecialchars($row['unit']); ?>" required>
                                        </div>

                                        <div>
                                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Estimasi</label>
                                            <input type="text" name="estimated_time" class="modern-input" value="<?= htmlspecialchars($row['estimated_time']); ?>">
                                        </div>

                                        <div>
                                            <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Status</label>
                                            <select name="status" class="modern-input">
                                                <option value="active" <?= $row['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="inactive" <?= $row['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                            </select>
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

                                <form method="POST" style="margin-top:12px;" onsubmit="return confirm('Nonaktifkan layanan ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?= $row['id']; ?>">

                                    <button type="submit" class="modern-btn-outline">
                                        Nonaktifkan
                                    </button>
                                </form>
                            </details>
                        </div>

                    <?php endwhile; ?>

                <?php else : ?>

                    <div class="modern-card" style="padding:36px;text-align:center;grid-column:1/-1;">
                        <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                            Belum Ada Layanan
                        </h2>

                        <p style="color:#64748b;">
                            Tambahkan layanan laundry agar pelanggan dapat membuat pesanan.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 900px) {
    section div[style*="grid-template-columns:repeat(2,1fr)"],
    form div[style*="grid-template-columns:repeat(2,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>