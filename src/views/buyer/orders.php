<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$statusFilter = $_GET['status'] ?? 'all';

$where = "WHERE laundry_orders.user_id='$user_id'";

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
        laundry_mitras.address AS mitra_address
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    LEFT JOIN laundry_mitras ON laundry_orders.mitra_id = laundry_mitras.id
    $where
    ORDER BY laundry_orders.id DESC
");

function orderBadgeBuyer($status)
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

function paymentBadgeBuyer($status)
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

function deliveryLabelBuyer($option)
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
    <title>Pesanan Saya</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/buyer-navbar.php"; ?>

<section style="padding:34px 7%;">

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px;">
        <div>
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Pelanggan</p>
            <h1 class="page-title">Pesanan Saya</h1>
            <p class="page-subtitle">Pantau status laundry, pembayaran, pickup, dan delivery.</p>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="create-order.php" class="modern-btn">Buat Pesanan</a>
            <a href="complaints.php" class="modern-btn-outline">Keluhan</a>
        </div>
    </div>

    <?php if (isset($_GET['created'])) : ?>
        <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
            Pesanan berhasil dibuat.
        </div>
    <?php endif; ?>

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
                                <?= htmlspecialchars($order['mitra_name'] ?: 'Seller Laundry'); ?> • <?= htmlspecialchars($order['mitra_phone'] ?: '-'); ?>
                            </p>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <?= orderBadgeBuyer($order['status']); ?>
                            <?= paymentBadgeBuyer($order['payment_status']); ?>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;">
                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                            <p style="color:#64748b;font-weight:700;font-size:12px;">Berat / Jumlah</p>
                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                <?= number_format($order['weight'] ?? 0, 2, ',', '.'); ?> <?= htmlspecialchars($order['unit']); ?>
                            </h3>
                        </div>

                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                            <p style="color:#64748b;font-weight:700;font-size:12px;">Harga Satuan</p>
                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                Rp <?= number_format($order['price_per_kg'] ?? 0, 0, ',', '.'); ?>
                            </h3>
                        </div>

                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                            <p style="color:#64748b;font-weight:700;font-size:12px;">Delivery</p>
                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                Rp <?= number_format($order['delivery_total'] ?? 0, 0, ',', '.'); ?>
                            </h3>
                        </div>

                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                            <p style="color:#64748b;font-weight:700;font-size:12px;">Total</p>
                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                Rp <?= number_format($order['total_price'] ?? 0, 0, ',', '.'); ?>
                            </h3>
                        </div>
                    </div>

                    <details style="background:#f8fdff;border:1px solid #d8f1ff;border-radius:18px;padding:15px;">
                        <summary style="cursor:pointer;font-weight:800;color:#0369a1;">
                            Detail Pesanan
                        </summary>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;">
                            <div>
                                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Alamat Pelanggan</p>
                                <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                    <?= nl2br(htmlspecialchars($order['address'] ?: '-')); ?>
                                </p>
                            </div>

                            <div>
                                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Delivery</p>
                                <p style="color:#64748b;line-height:1.7;font-size:13px;">
                                    <?= htmlspecialchars(deliveryLabelBuyer($order['delivery_option'])); ?><br>
                                    Pickup: Rp <?= number_format($order['pickup_fee'] ?? 0, 0, ',', '.'); ?><br>
                                    Antar: Rp <?= number_format($order['delivery_fee'] ?? 0, 0, ',', '.'); ?>
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

                        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                            <a href="complaints.php" class="modern-btn-outline">
                                Buat Keluhan
                            </a>
                        </div>
                    </details>

                </div>

            <?php endwhile; ?>

        <?php else : ?>

            <div class="modern-card" style="padding:36px;text-align:center;">
                <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Belum Ada Pesanan
                </h2>

                <p style="color:#64748b;margin-bottom:18px;">
                    Buat pesanan laundry pertama kamu sekarang.
                </p>

                <a href="create-order.php" class="modern-btn">
                    Buat Pesanan
                </a>
            </div>

        <?php endif; ?>

    </div>

</section>

<style>
@media (max-width: 1000px) {
    .order-card div[style*="grid-template-columns:repeat(4,1fr)"],
    details div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>