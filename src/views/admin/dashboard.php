<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM users
"))['total'] ?? 0;

$totalMitras = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM laundry_mitras
"))['total'] ?? 0;

$totalStaff = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM staff
"))['total'] ?? 0;

$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM laundry_orders
"))['total'] ?? 0;

$totalComplaints = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM complaints
"))['total'] ?? 0;

$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(total_price),0) AS total
    FROM laundry_orders
    WHERE payment_status='paid'
"))['total'] ?? 0;

$latestOrders = mysqli_query($conn, "
    SELECT 
        laundry_orders.*,
        laundry_services.service_name,
        laundry_mitras.mitra_name
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    LEFT JOIN laundry_mitras ON laundry_orders.mitra_id = laundry_mitras.id
    ORDER BY laundry_orders.id DESC
    LIMIT 6
");

function adminOrderBadge($status)
{
    $styles = [
        'diproses' => 'background:#e0f2fe;color:#0369a1;',
        'dicuci' => 'background:#dbeafe;color:#1d4ed8;',
        'selesai' => 'background:#dcfce7;color:#166534;',
        'diambil' => 'background:#f1f5f9;color:#334155;',
    ];

    $labels = [
        'diproses' => 'Diproses',
        'dicuci' => 'Dicuci',
        'selesai' => 'Selesai',
        'diambil' => 'Diambil',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
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
    <title>Dashboard Admin</title>
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

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                    Admin Panel
                </p>

                <h1 class="page-title">
                    Dashboard Admin
                </h1>

                <p class="page-subtitle">
                    Pantau seller, petugas, pesanan, pembayaran, dan keluhan pelanggan.
                </p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:16px;margin-bottom:22px;">

            <div class="modern-card" style="padding:18px;">
                <p style="color:#64748b;font-weight:800;">Pengguna</p>
                <h2 style="font-size:28px;font-weight:800;color:#0369a1;margin-top:6px;"><?= $totalUsers; ?></h2>
            </div>

            <div class="modern-card" style="padding:18px;">
                <p style="color:#64748b;font-weight:800;">Seller</p>
                <h2 style="font-size:28px;font-weight:800;color:#2563eb;margin-top:6px;"><?= $totalMitras; ?></h2>
            </div>

            <div class="modern-card" style="padding:18px;">
                <p style="color:#64748b;font-weight:800;">Petugas</p>
                <h2 style="font-size:28px;font-weight:800;color:#16a34a;margin-top:6px;"><?= $totalStaff; ?></h2>
            </div>

            <div class="modern-card" style="padding:18px;">
                <p style="color:#64748b;font-weight:800;">Pesanan</p>
                <h2 style="font-size:28px;font-weight:800;color:#f59e0b;margin-top:6px;"><?= $totalOrders; ?></h2>
            </div>

            <div class="modern-card" style="padding:18px;">
                <p style="color:#64748b;font-weight:800;">Keluhan</p>
                <h2 style="font-size:28px;font-weight:800;color:#dc2626;margin-top:6px;"><?= $totalComplaints; ?></h2>
            </div>

            <div class="modern-card" style="padding:18px;">
                <p style="color:#64748b;font-weight:800;">Pendapatan</p>
                <h2 style="font-size:22px;font-weight:800;color:#0369a1;margin-top:6px;">
                    Rp <?= number_format($totalRevenue, 0, ',', '.'); ?>
                </h2>
            </div>

        </div>

        <div class="modern-card" style="padding:22px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">
                        Pesanan Terbaru
                    </h2>

                    <p style="color:#64748b;margin-top:5px;">
                        Daftar pesanan terbaru seluruh seller.
                    </p>
                </div>

                <a href="orders.php" class="modern-btn-outline">
                    Lihat Semua
                </a>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">

                <?php if ($latestOrders && mysqli_num_rows($latestOrders) > 0) : ?>

                    <?php while ($order = mysqli_fetch_assoc($latestOrders)) : ?>

                        <div style="border:1px solid #d8f1ff;background:#f8fdff;border-radius:18px;padding:16px;">
                            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <div>
                                    <p style="font-weight:800;color:#0284c7;margin-bottom:5px;">
                                        Order #<?= $order['id']; ?>
                                    </p>

                                    <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;">
                                        <?= htmlspecialchars($order['customer_name']); ?>
                                    </h3>

                                    <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                        <?= htmlspecialchars($order['service_name']); ?> • <?= htmlspecialchars($order['mitra_name'] ?: 'Seller belum terhubung'); ?>
                                    </p>
                                </div>

                                <?= adminOrderBadge($order['status']); ?>
                            </div>
                        </div>

                    <?php endwhile; ?>

                <?php else : ?>

                    <div style="text-align:center;padding:24px;color:#64748b;">
                        Belum ada pesanan.
                    </div>

                <?php endif; ?>

            </div>
        </div>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    section div[style*="grid-template-columns:repeat(6,1fr)"] {
        grid-template-columns: repeat(2,1fr) !important;
    }
}

@media (max-width: 700px) {
    section div[style*="grid-template-columns:repeat(6,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>