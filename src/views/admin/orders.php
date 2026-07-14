<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';

$where = "WHERE 1=1";

if ($statusFilter !== 'all') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND laundry_orders.status='$safeStatus'";
}

$orders = mysqli_query($conn, "
    SELECT
        laundry_orders.*,
        laundry_services.service_name,
        laundry_services.unit,
        laundry_mitras.mitra_name,
        laundry_mitras.phone AS mitra_phone,
        staff_user.name AS staff_name,
        buyer_user.name AS buyer_name
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    LEFT JOIN laundry_mitras ON laundry_orders.mitra_id = laundry_mitras.id
    LEFT JOIN users AS staff_user ON laundry_orders.staff_id = staff_user.id
    LEFT JOIN users AS buyer_user ON laundry_orders.user_id = buyer_user.id
    $where
    ORDER BY laundry_orders.id DESC
");

function adminOrderStatusBadge($status)
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

function adminPaymentBadge($status)
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

function adminDeliveryLabel($option)
{
    $labels = [
        'self_service' => 'Antar dan ambil sendiri',
        'pickup_only' => 'Dijemput saja',
        'delivery_only' => 'Diantar saja',
        'pickup_delivery' => 'Antar jemput'
    ];

    return $labels[$option] ?? '-';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Semua Pesanan</title>
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
            <h1 class="page-title">Semua Pesanan</h1>
            <p class="page-subtitle">Admin dapat memantau seluruh pesanan dari semua seller.</p>
        </div>

        <div class="modern-card" style="padding:16px;margin-bottom:22px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php
                $filters = [
                    'all' => 'Semua',
                    'diproses' => 'Diproses',
                    'dicuci' => 'Dicuci',
                    'selesai' => 'Selesai',
                    'diambil' => 'Diambil'
                ];
                ?>

                <?php foreach ($filters as $key => $label) : ?>
                    <a href="orders.php?status=<?= $key; ?>" class="<?= $statusFilter === $key ? 'modern-btn' : 'modern-btn-outline'; ?>" style="padding:10px 16px;">
                        <?= $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:18px;">

            <?php if ($orders && mysqli_num_rows($orders) > 0) : ?>

                <?php while ($order = mysqli_fetch_assoc($orders)) : ?>

                    <div class="modern-card order-card" style="padding:22px;">

                        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                            <div>
                                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                    Order #<?= $order['id']; ?>
                                </p>

                                <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                    <?= htmlspecialchars($order['service_name']); ?>
                                </h2>

                                <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                    <?= htmlspecialchars($order['customer_name']); ?> • Seller: <?= htmlspecialchars($order['mitra_name'] ?: '-'); ?>
                                </p>
                            </div>

                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?= adminOrderStatusBadge($order['status']); ?>
                                <?= adminPaymentBadge($order['payment_status']); ?>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:14px;">
                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                <p style="color:#64748b;font-weight:700;font-size:12px;">Berat</p>
                                <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                    <?= number_format($order['weight'] ?? 0, 2, ',', '.'); ?> <?= htmlspecialchars($order['unit']); ?>
                                </h3>
                            </div>

                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                <p style="color:#64748b;font-weight:700;font-size:12px;">Harga</p>
                                <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                    Rp <?= number_format($order['price_per_kg'], 0, ',', '.'); ?>
                                </h3>
                            </div>

                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                <p style="color:#64748b;font-weight:700;font-size:12px;">Delivery</p>
                                <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                    Rp <?= number_format($order['delivery_total'], 0, ',', '.'); ?>
                                </h3>
                            </div>

                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                <p style="color:#64748b;font-weight:700;font-size:12px;">Total</p>
                                <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                    Rp <?= number_format($order['total_price'], 0, ',', '.'); ?>
                                </h3>
                            </div>

                            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                <p style="color:#64748b;font-weight:700;font-size:12px;">Petugas</p>
                                <h3 style="font-size:16px;font-weight:800;color:#0369a1;margin-top:5px;">
                                    <?= htmlspecialchars($order['staff_name'] ?: '-'); ?>
                                </h3>
                            </div>
                        </div>

                        <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                            <summary style="cursor:pointer;font-weight:800;color:#0369a1;">
                                Detail Pesanan
                            </summary>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;">
                                <div>
                                    <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Pelanggan</p>
                                    <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                        <?= htmlspecialchars($order['customer_name']); ?><br>
                                        <?= htmlspecialchars($order['phone']); ?><br>
                                        <?= nl2br(htmlspecialchars($order['address'])); ?>
                                    </p>
                                </div>

                                <div>
                                    <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Delivery</p>
                                    <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                        <?= htmlspecialchars(adminDeliveryLabel($order['delivery_option'])); ?><br>
                                        Pickup: Rp <?= number_format($order['pickup_fee'], 0, ',', '.'); ?><br>
                                        Antar: Rp <?= number_format($order['delivery_fee'], 0, ',', '.'); ?>
                                    </p>
                                </div>

                                <div>
                                    <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Alamat Pickup</p>
                                    <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                        <?= nl2br(htmlspecialchars($order['pickup_address'] ?: '-')); ?>
                                    </p>
                                </div>

                                <div>
                                    <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Alamat Pengantaran</p>
                                    <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                        <?= nl2br(htmlspecialchars($order['delivery_address'] ?: '-')); ?>
                                    </p>
                                </div>
                            </div>

                            <div style="margin-top:14px;">
                                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Catatan</p>
                                <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                    <?= nl2br(htmlspecialchars($order['notes'] ?: '-')); ?>
                                </p>
                            </div>
                        </details>

                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="modern-card" style="padding:36px;text-align:center;">
                    <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                        Belum Ada Pesanan
                    </h2>

                    <p style="color:#64748b;">
                        Pesanan pelanggan akan muncul di halaman ini.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    .order-card div[style*="grid-template-columns:repeat(5,1fr)"],
    details div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>