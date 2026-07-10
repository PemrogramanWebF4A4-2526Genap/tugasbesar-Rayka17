<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer_service') {
    header("Location: ../public/login.php");
    exit;
}

$totalComplaints = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM complaints
"))['total'] ?? 0;

$pendingComplaints = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM complaints WHERE status='pending'
"))['total'] ?? 0;

$processComplaints = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM complaints WHERE status='process'
"))['total'] ?? 0;

$doneComplaints = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM complaints WHERE status='done'
"))['total'] ?? 0;

$latestComplaints = mysqli_query($conn, "
    SELECT 
        complaints.*,
        users.name AS buyer_name,
        laundry_orders.customer_name
    FROM complaints
    LEFT JOIN users ON complaints.buyer_id = users.id
    LEFT JOIN laundry_orders ON complaints.order_id = laundry_orders.id
    ORDER BY complaints.id DESC
    LIMIT 6
");

function csComplaintBadge($status)
{
    $styles = [
        'pending' => 'background:#fef3c7;color:#92400e;',
        'process' => 'background:#dbeafe;color:#1d4ed8;',
        'done' => 'background:#dcfce7;color:#166534;',
    ];

    $labels = [
        'pending' => 'Menunggu',
        'process' => 'Diproses',
        'done' => 'Selesai',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Customer Service</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/customer-service-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/customer-service-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Customer Service</p>
                <h1 class="page-title">Dashboard Keluhan</h1>
                <p class="page-subtitle">Pantau dan tangani keluhan pelanggan.</p>
            </div>

            <a href="complaints.php" class="modern-btn">Kelola Keluhan</a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px;">
            <div class="modern-card" style="padding:20px;">
                <p style="color:#64748b;font-weight:800;">Total Keluhan</p>
                <h2 style="font-size:30px;font-weight:800;color:#0369a1;margin-top:6px;"><?= $totalComplaints; ?></h2>
            </div>

            <div class="modern-card" style="padding:20px;">
                <p style="color:#64748b;font-weight:800;">Menunggu</p>
                <h2 style="font-size:30px;font-weight:800;color:#f59e0b;margin-top:6px;"><?= $pendingComplaints; ?></h2>
            </div>

            <div class="modern-card" style="padding:20px;">
                <p style="color:#64748b;font-weight:800;">Diproses</p>
                <h2 style="font-size:30px;font-weight:800;color:#2563eb;margin-top:6px;"><?= $processComplaints; ?></h2>
            </div>

            <div class="modern-card" style="padding:20px;">
                <p style="color:#64748b;font-weight:800;">Selesai</p>
                <h2 style="font-size:30px;font-weight:800;color:#16a34a;margin-top:6px;"><?= $doneComplaints; ?></h2>
            </div>
        </div>

        <div class="modern-card" style="padding:22px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:18px;">
                <div>
                    <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Keluhan Terbaru</h2>
                    <p style="color:#64748b;margin-top:5px;">Daftar keluhan terbaru pelanggan.</p>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <?php if ($latestComplaints && mysqli_num_rows($latestComplaints) > 0) : ?>
                    <?php while ($row = mysqli_fetch_assoc($latestComplaints)) : ?>
                        <div style="border:1px solid #d8f1ff;background:#f8fdff;border-radius:18px;padding:16px;">
                            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <div>
                                    <p style="font-weight:800;color:#0284c7;margin-bottom:5px;">
                                        Keluhan #<?= $row['id']; ?> <?= $row['order_id'] ? '• Order #' . $row['order_id'] : ''; ?>
                                    </p>

                                    <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0;">
                                        <?= htmlspecialchars($row['title']); ?>
                                    </h3>

                                    <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                        <?= htmlspecialchars($row['buyer_name'] ?: $row['customer_name'] ?: 'Pelanggan'); ?>
                                    </p>
                                </div>

                                <?= csComplaintBadge($row['status']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div style="text-align:center;padding:24px;color:#64748b;">
                        Belum ada keluhan.
                    </div>
                <?php endif; ?>
            </div>
        </div>

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