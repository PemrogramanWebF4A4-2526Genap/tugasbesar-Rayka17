<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

$mitraFilter = $_GET['mitra_id'] ?? 'all';

$where = "WHERE 1=1";

if ($mitraFilter !== 'all') {
    $safeMitra = (int) $mitraFilter;
    $where .= " AND laundry_services.mitra_id='$safeMitra'";
}

$mitras = mysqli_query($conn, "
    SELECT id, mitra_name
    FROM laundry_mitras
    ORDER BY mitra_name ASC
");

$services = mysqli_query($conn, "
    SELECT
        laundry_services.*,
        laundry_mitras.mitra_name,
        laundry_mitras.phone AS mitra_phone
    FROM laundry_services
    LEFT JOIN laundry_mitras ON laundry_services.mitra_id = laundry_mitras.id
    $where
    ORDER BY laundry_services.id DESC
");

function adminServiceBadge($status)
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
    <title>Semua Layanan</title>
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
            <h1 class="page-title">Semua Layanan</h1>
            <p class="page-subtitle">Pantau layanan laundry dari seluruh seller.</p>
        </div>

        <div class="modern-card" style="padding:16px;margin-bottom:22px;">
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
                <div style="min-width:260px;">
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">
                        Filter Seller
                    </label>

                    <select name="mitra_id" class="modern-input">
                        <option value="all">Semua seller</option>

                        <?php if ($mitras && mysqli_num_rows($mitras) > 0) : ?>
                            <?php while ($mitra = mysqli_fetch_assoc($mitras)) : ?>
                                <option value="<?= $mitra['id']; ?>" <?= $mitraFilter == $mitra['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($mitra['mitra_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="modern-btn">
                    Terapkan
                </button>
            </form>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">

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
                                    <?= htmlspecialchars($row['mitra_name'] ?: '-'); ?> • <?= htmlspecialchars($row['service_category'] ?: '-'); ?>
                                </p>
                            </div>

                            <?= adminServiceBadge($row['status']); ?>
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
                                Detail Layanan
                            </summary>

                            <div style="margin-top:14px;">
                                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Estimasi</p>
                                <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                    <?= htmlspecialchars($row['estimated_time'] ?: '-'); ?>
                                </p>
                            </div>

                            <div style="margin-top:14px;">
                                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Deskripsi</p>
                                <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                    <?= nl2br(htmlspecialchars($row['description'] ?: '-')); ?>
                                </p>
                            </div>

                            <div style="margin-top:14px;">
                                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Kontak Seller</p>
                                <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                    <?= htmlspecialchars($row['mitra_phone'] ?: '-'); ?>
                                </p>
                            </div>
                        </details>
                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="modern-card" style="padding:36px;text-align:center;grid-column:1/-1;">
                    <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                        Belum Ada Layanan
                    </h2>

                    <p style="color:#64748b;">
                        Layanan dari seller akan muncul di halaman ini.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    section div[style*="grid-template-columns:repeat(3,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>