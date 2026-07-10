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
$user_id = $user['id'];

$staff = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        staff.*,
        laundry_mitras.mitra_name
    FROM staff
    JOIN laundry_mitras ON staff.mitra_id = laundry_mitras.id
    WHERE staff.user_id='$user_id'
    LIMIT 1
"));

$staff_id = $staff['id'] ?? 0;
$mitra_id = $staff['mitra_id'] ?? 0;

$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_orders
    WHERE mitra_id='$mitra_id'
"))['total'] ?? 0;

$myTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_staff_tasks
    WHERE staff_id='$staff_id'
"))['total'] ?? 0;

$waitingTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_staff_tasks
    WHERE mitra_id='$mitra_id'
    AND task_status='waiting'
"))['total'] ?? 0;

$completedTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_staff_tasks
    WHERE staff_id='$staff_id'
    AND task_status='completed'
"))['total'] ?? 0;
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

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                    Dashboard Petugas
                </p>

                <h1 class="page-title">
                    Halo, <?= htmlspecialchars($user['name']); ?>
                </h1>

                <p class="page-subtitle">
                    Kamu terhubung dengan <?= htmlspecialchars($staff['mitra_name'] ?? 'mitra laundry'); ?>.
                </p>
            </div>

            <a href="petugas-tasks.php" class="modern-btn">
                Lihat Tugas
            </a>
        </div>

        <?php if (!$staff) : ?>

            <div class="modern-card" style="padding:34px;text-align:center;">
                <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Akun Belum Terhubung
                </h2>

                <p style="color:#64748b;">
                    Seller perlu menghubungkan akun ini ke data petugas.
                </p>
            </div>

        <?php else : ?>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px;">
                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Pesanan Mitra</p>
                    <h2 style="font-size:30px;font-weight:800;color:#0369a1;margin-top:6px;"><?= $totalOrders; ?></h2>
                </div>

                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Tugas Saya</p>
                    <h2 style="font-size:30px;font-weight:800;color:#2563eb;margin-top:6px;"><?= $myTasks; ?></h2>
                </div>

                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Tugas Menunggu</p>
                    <h2 style="font-size:30px;font-weight:800;color:#f59e0b;margin-top:6px;"><?= $waitingTasks; ?></h2>
                </div>

                <div class="modern-card" style="padding:20px;">
                    <p style="color:#64748b;font-weight:800;">Tugas Selesai</p>
                    <h2 style="font-size:30px;font-weight:800;color:#16a34a;margin-top:6px;"><?= $completedTasks; ?></h2>
                </div>
            </div>

            <div class="modern-card" style="padding:30px;text-align:center;">
                <h2 style="font-size:25px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                    Panel Petugas Siap Digunakan
                </h2>

                <p style="color:#64748b;margin-bottom:18px;">
                    Petugas dapat mengambil tugas dan membantu proses pesanan laundry.
                </p>

                <a href="petugas-tasks.php" class="modern-btn">
                    Kelola Tugas
                </a>
            </div>

        <?php endif; ?>

    </section>

</main>

<style>
@media (max-width: 900px) {
    section div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>