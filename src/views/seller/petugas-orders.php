<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'petugas') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$staff = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        staff.*,
        laundry_mitras.mitra_name
    FROM staff
    JOIN laundry_mitras ON staff.mitra_id = laundry_mitras.id
    WHERE staff.user_id='$user_id'
    LIMIT 1
"));

$mitra_id = $staff['mitra_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order') {
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'diproses');
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

        $allowedStatus = ['diproses', 'dicuci', 'selesai', 'diambil'];

        if (!in_array($status, $allowedStatus)) {
            header("Location: petugas-orders.php?error=1");
            exit;
        }

        $order = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT *
            FROM laundry_orders
            WHERE id='$order_id'
            AND mitra_id='$mitra_id'
            LIMIT 1
        "));

        if (!$order) {
            header("Location: petugas-orders.php?error=1");
            exit;
        }

        $old_status = $order['status'];
        $buyer_id = (int) $order['user_id'];

        mysqli_query($conn, "
            UPDATE laundry_orders
            SET 
                status='$status',
                staff_id='$user_id',
                notes='$notes'
            WHERE id='$order_id'
            AND mitra_id='$mitra_id'
        ");

        mysqli_query($conn, "
            INSERT INTO laundry_order_status_logs(order_id,user_id,old_status,new_status,note)
            VALUES(
                '$order_id',
                '$user_id',
                '$old_status',
                '$status',
                'Petugas memperbarui status pesanan.'
            )
        ");

        mysqli_query($conn, "
            INSERT INTO notifications(user_id,title,message,is_read)
            VALUES(
                '$buyer_id',
                'Status Pesanan Diperbarui',
                'Status pesanan #$order_id diperbarui oleh petugas.',
                0
            )
        ");

        header("Location: petugas-orders.php?updated=1");
        exit;
    }

    header("Location: petugas-orders.php?error=1");
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
        laundry_mitras.mitra_name,
        users.name AS buyer_name
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    JOIN laundry_mitras ON laundry_orders.mitra_id = laundry_mitras.id
    JOIN users ON laundry_orders.user_id = users.id
    $where
    ORDER BY laundry_orders.id DESC
");

function petugasOrderBadge($status)
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

function petugasPaymentBadge($status)
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
    <title>Pesanan Petugas</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/seller-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/seller-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Panel Petugas</p>
                <h1 class="page-title">Pesanan Laundry</h1>
                <p class="page-subtitle">
                    <?= $staff ? htmlspecialchars($staff['mitra_name']) : 'Akun belum terhubung ke seller.'; ?>
                </p>
            </div>

            <a href="petugas-tasks.php" class="modern-btn">Tugas Pickup & Delivery</a>
        </div>

        <?php if (isset($_GET['updated'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Status pesanan berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])) : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
                Gagal memperbarui pesanan.
            </div>
        <?php endif; ?>

        <?php if (!$staff) : ?>

            <div class="modern-card" style="padding:34px;text-align:center;">
                <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Akun Petugas Belum Terhubung
                </h2>
                <p style="color:#64748b;">Hubungi seller atau admin.</p>
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
                        <a href="petugas-orders.php?status=<?= $key; ?>" class="<?= $statusFilter === $key ? 'modern-btn' : 'modern-btn-outline'; ?>" style="padding:10px 16px;">
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
                                            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Order #<?= $order['id']; ?></p>

                                            <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                                <?= htmlspecialchars($order['service_name']); ?>
                                            </h2>

                                            <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                                <?= htmlspecialchars($order['customer_name']); ?> • <?= htmlspecialchars($order['phone']); ?>
                                            </p>
                                        </div>

                                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                            <?= petugasOrderBadge($order['status']); ?>
                                            <?= petugasPaymentBadge($order['payment_status']); ?>
                                        </div>
                                    </div>

                                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;">
                                        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:14px;">
                                            <p style="color:#64748b;font-weight:700;font-size:12px;">Berat</p>
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
                                        <summary style="cursor:pointer;font-weight:800;color:#0369a1;">Detail Pesanan</summary>

                                        <p style="color:#64748b;line-height:1.7;font-size:13px;margin-top:14px;">
                                            <b>Alamat:</b><br>
                                            <?= nl2br(htmlspecialchars($order['address'] ?: '-')); ?><br><br>

                                            <b>Catatan:</b><br>
                                            <?= nl2br(htmlspecialchars($order['notes'] ?: '-')); ?>
                                        </p>
                                    </details>
                                </div>

                                <div class="modern-card" style="padding:19px;background:#f8fdff;">
                                    <h3 style="font-size:20px;font-weight:800;color:#0369a1;margin-bottom:14px;">
                                        Update Status
                                    </h3>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_order">
                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">

                                        <div style="margin-bottom:13px;">
                                            <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Status</label>
                                            <select name="status" class="modern-input" required>
                                                <option value="diproses" <?= $order['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                                <option value="dicuci" <?= $order['status'] === 'dicuci' ? 'selected' : ''; ?>>Dicuci</option>
                                                <option value="selesai" <?= $order['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                <option value="diambil" <?= $order['status'] === 'diambil' ? 'selected' : ''; ?>>Diambil</option>
                                            </select>
                                        </div>

                                        <div style="margin-bottom:15px;">
                                            <label style="display:block;font-weight:800;color:#0369a1;margin-bottom:7px;">Catatan</label>
                                            <textarea name="notes" class="modern-input" rows="3"><?= htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="submit" class="modern-btn" style="width:100%;">Simpan</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="modern-card" style="padding:36px;text-align:center;">
                        <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">Belum Ada Pesanan</h2>
                        <p style="color:#64748b;">Pesanan pelanggan akan muncul di halaman ini.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 1100px) {
    .order-card > div,
    .order-card div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns:1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>