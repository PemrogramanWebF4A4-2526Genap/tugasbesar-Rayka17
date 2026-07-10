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
        laundry_mitras.mitra_name,
        laundry_mitras.phone AS mitra_phone,
        laundry_mitras.address AS mitra_address
    FROM staff
    JOIN laundry_mitras ON staff.mitra_id = laundry_mitras.id
    WHERE staff.user_id='$user_id'
    LIMIT 1
"));

$mitra_id = $staff['mitra_id'] ?? 0;

$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_orders
    WHERE mitra_id='$mitra_id'
"))['total'] ?? 0;

$waitingTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_staff_tasks t
    JOIN laundry_orders o ON t.order_id = o.id
    WHERE o.mitra_id='$mitra_id'
    AND t.task_status='waiting'
"))['total'] ?? 0;

$myTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_staff_tasks
    WHERE staff_id='$user_id'
"))['total'] ?? 0;

$completedTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_staff_tasks
    WHERE staff_id='$user_id'
    AND task_status='completed'
"))['total'] ?? 0;

$latestTasks = mysqli_query($conn, "
    SELECT 
        t.*,
        o.customer_name,
        o.phone,
        o.status AS order_status,
        s.service_name
    FROM laundry_staff_tasks t
    JOIN laundry_orders o ON t.order_id = o.id
    JOIN laundry_services s ON o.service_id = s.id
    WHERE o.mitra_id='$mitra_id'
    AND (
        t.task_status='waiting'
        OR t.staff_id='$user_id'
    )
    ORDER BY t.id DESC
    LIMIT 6
");

function petugasTaskBadge($status)
{
    $styles = [
        'waiting' => 'background:#fef3c7;color:#92400e;',
        'assigned' => 'background:#dbeafe;color:#1d4ed8;',
        'on_process' => 'background:#e0f2fe;color:#0369a1;',
        'completed' => 'background:#dcfce7;color:#166534;',
        'cancelled' => 'background:#fee2e2;color:#b91c1c;',
    ];

    $labels = [
        'waiting' => 'Menunggu',
        'assigned' => 'Ditugaskan',
        'on_process' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}

function petugasTaskType($type)
{
    return $type === 'pickup' ? 'Pickup / Jemput' : 'Delivery / Antar';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Petugas</title>
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
                <h1 class="page-title">Dashboard Petugas</h1>
                <p class="page-subtitle">
                    <?= $staff ? htmlspecialchars($staff['mitra_name']) : 'Akun petugas belum terhubung ke seller.'; ?>
                </p>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="petugas-orders.php" class="modern-btn">Pesanan</a>
                <a href="petugas-tasks.php" class="modern-btn-outline">Tugas</a>
            </div>
        </div>

        <?php if (!$staff) : ?>

            <div class="modern-card" style="padding:34px;text-align:center;">
                <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Akun Petugas Belum Terhubung
                </h2>
                <p style="color:#64748b;">
                    Seller atau admin perlu menghubungkan akun ini ke data petugas.
                </p>
            </div>

        <?php else : ?>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px;">
                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Pesanan Seller</p>
                    <h2 style="font-size:30px;font-weight:800;color:#0369a1;margin-top:6px;"><?= $totalOrders; ?></h2>
                </div>

                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Tugas Menunggu</p>
                    <h2 style="font-size:30px;font-weight:800;color:#f59e0b;margin-top:6px;"><?= $waitingTasks; ?></h2>
                </div>

                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Tugas Saya</p>
                    <h2 style="font-size:30px;font-weight:800;color:#2563eb;margin-top:6px;"><?= $myTasks; ?></h2>
                </div>

                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Selesai</p>
                    <h2 style="font-size:30px;font-weight:800;color:#16a34a;margin-top:6px;"><?= $completedTasks; ?></h2>
                </div>
            </div>

            <div class="modern-card" style="padding:22px;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:18px;">
                    <div>
                        <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Tugas Terbaru</h2>
                        <p style="color:#64748b;margin-top:5px;">Daftar tugas pickup dan delivery.</p>
                    </div>

                    <a href="petugas-tasks.php" class="modern-btn-outline">Lihat Semua</a>
                </div>

                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php if ($latestTasks && mysqli_num_rows($latestTasks) > 0) : ?>
                        <?php while ($task = mysqli_fetch_assoc($latestTasks)) : ?>
                            <div style="border:1px solid #d8f1ff;background:#f8fdff;border-radius:18px;padding:16px;">
                                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                    <div>
                                        <p style="font-weight:800;color:#0284c7;margin-bottom:5px;">
                                            Tugas #<?= $task['id']; ?> • Order #<?= $task['order_id']; ?>
                                        </p>

                                        <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;">
                                            <?= htmlspecialchars(petugasTaskType($task['task_type'])); ?>
                                        </h3>

                                        <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                            <?= htmlspecialchars($task['customer_name']); ?> • <?= htmlspecialchars($task['service_name']); ?>
                                        </p>
                                    </div>

                                    <?= petugasTaskBadge($task['task_status']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div style="text-align:center;padding:24px;color:#64748b;">
                            Belum ada tugas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 900px) {
    section div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns:1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>