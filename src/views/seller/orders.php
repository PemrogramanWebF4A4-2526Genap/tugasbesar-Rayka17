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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mitra) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order') {
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $weight = (float) ($_POST['weight'] ?? 0);
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'diproses');
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status'] ?? 'unpaid');
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

        $allowedStatus = ['diproses', 'dicuci', 'selesai', 'diambil'];
        $allowedPayment = ['unpaid', 'waiting_confirmation', 'paid', 'cancelled'];

        if (!in_array($status, $allowedStatus) || !in_array($payment_status, $allowedPayment)) {
            header("Location: orders.php?error=1");
            exit;
        }

        $order = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT
                laundry_orders.*,
                laundry_services.price_per_kg AS service_price
            FROM laundry_orders
            JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
            WHERE laundry_orders.id='$order_id'
            AND laundry_orders.mitra_id='$mitra_id'
            LIMIT 1
        "));

        if (!$order) {
            header("Location: orders.php?error=1");
            exit;
        }

        if ($weight < 0) {
            $weight = 0;
        }

        $old_status = $order['status'];
        $buyer_id = (int) $order['user_id'];
        $price_per_kg = (int) ($order['price_per_kg'] ?: $order['service_price']);
        $delivery_total = (int) ($order['delivery_total'] ?? 0);
        $total_price = ($weight * $price_per_kg) + $delivery_total;

        mysqli_query($conn, "
            UPDATE laundry_orders
            SET
                weight='$weight',
                price_per_kg='$price_per_kg',
                total_price='$total_price',
                status='$status',
                payment_status='$payment_status',
                notes='$notes'
            WHERE id='$order_id'
            AND mitra_id='$mitra_id'
        ");

        if (mysqli_error($conn)) {
            die('SQL Update Order Error: ' . mysqli_error($conn));
        }

        mysqli_query($conn, "
            INSERT INTO laundry_order_status_logs(order_id,user_id,old_status,new_status,note)
            VALUES(
                '$order_id',
                '$user_id',
                '$old_status',
                '$status',
                'Seller memperbarui berat, status, dan pembayaran pesanan.'
            )
        ");

        $payment = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id
            FROM laundry_payments
            WHERE order_id='$order_id'
            LIMIT 1
        "));

        if ($payment) {
            if ($payment_status === 'paid') {
                mysqli_query($conn, "
                    UPDATE laundry_payments
                    SET
                        amount='$total_price',
                        payment_status='$payment_status',
                        paid_at=IF(paid_at IS NULL, NOW(), paid_at)
                    WHERE order_id='$order_id'
                ");
            } else {
                mysqli_query($conn, "
                    UPDATE laundry_payments
                    SET
                        amount='$total_price',
                        payment_status='$payment_status'
                    WHERE order_id='$order_id'
                ");
            }
        } else {
            $paidAtValue = $payment_status === 'paid' ? "NOW()" : "NULL";

            mysqli_query($conn, "
                INSERT INTO laundry_payments(order_id,user_id,payment_method,amount,payment_status,paid_at)
                VALUES(
                    '$order_id',
                    '$buyer_id',
                    '{$order['payment_method']}',
                    '$total_price',
                    '$payment_status',
                    $paidAtValue
                )
            ");
        }

        mysqli_query($conn, "
            INSERT INTO notifications(user_id,title,message,is_read)
            VALUES(
                '$buyer_id',
                'Pesanan Laundry Diperbarui',
                'Pesanan #$order_id diperbarui seller. Total pembayaran: Rp " . number_format($total_price, 0, ',', '.') . ".',
                0
            )
        ");

        header("Location: orders.php?updated=1");
        exit;
    }

    header("Location: orders.php?error=1");
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';

$where = "WHERE laundry_orders.mitra_id='$mitra_id'";

if ($statusFilter !== 'all') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND laundry_orders.status='$safeStatus'";
}

$orders = mysqli_query($conn, "
    SELECT
        laundry_orders.*,
        laundry_services.service_name,
        laundry_services.unit,
        users.name AS buyer_name
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    JOIN users ON laundry_orders.user_id = users.id
    $where
    ORDER BY laundry_orders.id DESC
");

function orderBadgeSeller($status)
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

function paymentBadgeSeller($status)
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

function deliveryLabelSeller($option)
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
    <title>Kelola Pesanan</title>
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
            <h1 class="page-title">Kelola Pesanan</h1>
            <p class="page-subtitle">Input berat, hitung total otomatis, update status laundry, dan konfirmasi pembayaran.</p>
        </div>

        <?php if (isset($_GET['updated'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Pesanan berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])) : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Gagal memperbarui pesanan.
            </div>
        <?php endif; ?>

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

                            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

                                <div>
                                    <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                                        <div>
                                            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                                Order #<?= $order['id']; ?>
                                            </p>

                                            <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                                <?= htmlspecialchars($order['service_name']); ?>
                                            </h2>

                                            <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                                <?= htmlspecialchars($order['customer_name']); ?> • <?= htmlspecialchars($order['phone']); ?>
                                            </p>
                                        </div>

                                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                            <?= orderBadgeSeller($order['status']); ?>
                                            <?= paymentBadgeSeller($order['payment_status']); ?>
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
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Harga</p>
                                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                                Rp <?= number_format($order['price_per_kg'], 0, ',', '.'); ?>
                                            </h3>
                                        </div>

                                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Delivery</p>
                                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                                Rp <?= number_format($order['delivery_total'], 0, ',', '.'); ?>
                                            </h3>
                                        </div>

                                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Total</p>
                                            <h3 style="font-size:17px;font-weight:800;color:#0369a1;margin-top:5px;">
                                                Rp <?= number_format($order['total_price'], 0, ',', '.'); ?>
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
                                                    <?= htmlspecialchars(deliveryLabelSeller($order['delivery_option'])); ?><br>
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

                                <div class="modern-card" style="padding:19px;background:#f8fdff;">
                                    <h3 style="font-size:20px;font-weight:800;color:#0369a1;margin-bottom:14px;">
                                        Update Pesanan
                                    </h3>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_order">
                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">

                                        <div style="margin-bottom:13px;">
                                            <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Berat / Jumlah</label>
                                            <input type="number" step="0.01" name="weight" class="modern-input" value="<?= htmlspecialchars($order['weight'] ?? 0); ?>" required>
                                        </div>

                                        <div style="margin-bottom:13px;">
                                            <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Status Laundry</label>
                                            <select name="status" class="modern-input" required>
                                                <option value="diproses" <?= $order['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                                <option value="dicuci" <?= $order['status'] === 'dicuci' ? 'selected' : ''; ?>>Dicuci</option>
                                                <option value="selesai" <?= $order['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                <option value="diambil" <?= $order['status'] === 'diambil' ? 'selected' : ''; ?>>Diambil</option>
                                            </select>
                                        </div>

                                        <div style="margin-bottom:13px;">
                                            <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Status Pembayaran</label>
                                            <select name="payment_status" class="modern-input" required>
                                                <option value="unpaid" <?= $order['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Belum Bayar</option>
                                                <option value="waiting_confirmation" <?= $order['payment_status'] === 'waiting_confirmation' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                                <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Lunas</option>
                                                <option value="cancelled" <?= $order['payment_status'] === 'cancelled' ? 'selected' : ''; ?>>Batal</option>
                                            </select>
                                        </div>

                                        <div style="margin-bottom:15px;">
                                            <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Catatan</label>
                                            <textarea name="notes" class="modern-input" rows="3"><?= htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="submit" class="modern-btn" style="width:100%;">
                                            Simpan
                                        </button>
                                    </form>
                                </div>

                            </div>

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

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    .order-card > div,
    .order-card div[style*="grid-template-columns:repeat(4,1fr)"],
    details div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>