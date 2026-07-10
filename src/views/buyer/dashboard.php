<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'courier') {
    header("Location: ../public/login.php");
    exit;
}

$courier = $_SESSION['user'];
$courier_id = $courier['id'];

$waitingTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_delivery_tasks
    WHERE task_status='waiting'
"))['total'] ?? 0;

$myTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_delivery_tasks
    WHERE courier_id='$courier_id'
"))['total'] ?? 0;

$onProcessTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_delivery_tasks
    WHERE courier_id='$courier_id'
    AND task_status IN ('assigned','on_process')
"))['total'] ?? 0;

$completedTasks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM laundry_delivery_tasks
    WHERE courier_id='$courier_id'
    AND task_status='completed'
"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Kurir</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/courier-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/courier-topbar.php"; ?>

    <section style="padding:28px;">

        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:8px;">
                    Dashboard Kurir
                </p>

                <h1 class="page-title">
                    Halo, <?= htmlspecialchars($courier['name']); ?>
                </h1>

                <p class="page-subtitle">
                    Kelola tugas jemput dan antar laundry pelanggan.
                </p>
            </div>

            <a href="tasks.php" class="modern-btn">
                Lihat Tugas
            </a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:26px;">
            <div class="modern-card" style="padding:22px;">
                <p style="color:#64748b;font-weight:800;">Tugas Menunggu</p>
                <h2 style="font-size:32px;font-weight:800;color:#f59e0b;margin:6px 0 0;">
                    <?= $waitingTasks; ?>
                </h2>
            </div>

            <div class="modern-card" style="padding:22px;">
                <p style="color:#64748b;font-weight:800;">Tugas Saya</p>
                <h2 style="font-size:32px;font-weight:800;color:#0369a1;margin:6px 0 0;">
                    <?= $myTasks; ?>
                </h2>
            </div>

            <div class="modern-card" style="padding:22px;">
                <p style="color:#64748b;font-weight:800;">Dalam Proses</p>
                <h2 style="font-size:32px;font-weight:800;color:#2563eb;margin:6px 0 0;">
                    <?= $onProcessTasks; ?>
                </h2>
            </div>

            <div class="modern-card" style="padding:22px;">
                <p style="color:#64748b;font-weight:800;">Selesai</p>
                <h2 style="font-size:32px;font-weight:800;color:#16a34a;margin:6px 0 0;">
                    <?= $completedTasks; ?>
                </h2>
            </div>
        </div>

        <div class="modern-card" style="padding:28px;text-align:center;">
            <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                Panel Kurir Siap Digunakan
            </h2>

            <p style="color:#64748b;margin-bottom:20px;">
                Tugas delivery akan muncul ketika pelanggan memilih opsi jemput, antar, atau antar jemput.
            </p>

            <a href="tasks.php" class="modern-btn">
                Kelola Tugas Delivery
            </a>
        </div>

    </section>

</main>

<style>
@media (max-width: 1000px) {
    section div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns: repeat(2,1fr) !important;
    }
}

@media (max-width: 650px) {
    section div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>