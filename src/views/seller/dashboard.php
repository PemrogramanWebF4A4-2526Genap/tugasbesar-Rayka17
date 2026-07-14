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

$mitra = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM laundry_mitras
    WHERE user_id='$user_id'
    LIMIT 1
"));

$mitra_id = $mitra['id'] ?? 0;

$totalServices = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_services
    WHERE mitra_id='$mitra_id'
"))['total'] ?? 0;

$totalActiveServices = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_services
    WHERE mitra_id='$mitra_id'
    AND status='active'
"))['total'] ?? 0;

$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_orders
    WHERE mitra_id='$mitra_id'
"))['total'] ?? 0;

$totalStaff = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM staff
    WHERE mitra_id='$mitra_id'
"))['total'] ?? 0;

$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(total_price),0) AS total
    FROM laundry_orders
    WHERE mitra_id='$mitra_id'
    AND payment_status='paid'
"))['total'] ?? 0;

$latestOrders = mysqli_query($conn, "
    SELECT 
        laundry_orders.*,
        laundry_services.service_name,
        laundry_services.unit
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    WHERE laundry_orders.mitra_id='$mitra_id'
    ORDER BY laundry_orders.id DESC
    LIMIT 6
");

function sellerOrderBadge($status)
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

function sellerPaymentBadge($status)
{
    $styles = [
        'unpaid' => 'background:#fee2e2;color:#b91c1c;',
        'waiting_confirmation' => 'background:#fef3c7;color:#92400e;',
        'paid' => 'background:#dcfce7;color:#166534;',
        'cancelled' => 'background:#f1f5f9;color:#334155;',
    ];

    $labels = [
        'unpaid' => 'Belum Bayar',
        'waiting_confirmation' => 'Menunggu',
        'paid' => 'Lunas',
        'cancelled' => 'Batal',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Dashboard Seller</title>
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

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                    Seller Panel
                </p>

                <h1 class="page-title">
                    Dashboard Seller
                </h1>

                <p class="page-subtitle">
                    <?= $mitra ? htmlspecialchars($mitra['mitra_name']) : 'Akun belum terhubung ke data seller.'; ?>
                </p>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="orders.php" class="modern-btn">
                    Kelola Pesanan
                </a>

                <a href="services.php" class="modern-btn-outline">
                    Kelola Layanan
                </a>
            </div>
        </div>

        <?php if (!$mitra) : ?>

            <div class="modern-card" style="padding:34px;text-align:center;">
                <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Data Seller Belum Terhubung
                </h2>

                <p style="color:#64748b;">
                    Admin perlu menghubungkan akun ini ke data laundry_mitras.
                </p>
            </div>

        <?php else : ?>

            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:22px;">

                <div class="modern-card" style="padding:18px;">
                    <p style="color:#64748b;font-weight:800;">Pesanan</p>
                    <h2 style="font-size:28px;font-weight:800;color:#0369a1;margin-top:6px;"><?= $totalOrders; ?></h2>
                </div>

                <div class="modern-card" style="padding:18px;">
                    <p style="color:#64748b;font-weight:800;">Layanan</p>
                    <h2 style="font-size:28px;font-weight:800;color:#2563eb;margin-top:6px;"><?= $totalServices; ?></h2>
                </div>

                <div class="modern-card" style="padding:18px;">
                    <p style="color:#64748b;font-weight:800;">Layanan Aktif</p>
                    <h2 style="font-size:28px;font-weight:800;color:#16a34a;margin-top:6px;"><?= $totalActiveServices; ?></h2>
                </div>

                <div class="modern-card" style="padding:18px;">
                    <p style="color:#64748b;font-weight:800;">Petugas</p>
                    <h2 style="font-size:28px;font-weight:800;color:#f59e0b;margin-top:6px;"><?= $totalStaff; ?></h2>
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
                            Ringkasan pesanan terbaru dari pelanggan.
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
                                            <?= htmlspecialchars($order['service_name']); ?> • 
                                            <?= number_format($order['weight'] ?? 0, 2, ',', '.'); ?> <?= htmlspecialchars($order['unit']); ?> • 
                                            Rp <?= number_format($order['total_price'], 0, ',', '.'); ?>
                                        </p>
                                    </div>

                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <?= sellerOrderBadge($order['status']); ?>
                                        <?= sellerPaymentBadge($order['payment_status']); ?>
                                    </div>
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

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    section div[style*="grid-template-columns:repeat(5,1fr)"] {
        grid-template-columns: repeat(2,1fr) !important;
    }
}

@media (max-width: 700px) {
    section div[style*="grid-template-columns:repeat(5,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>