<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'buyer') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

$notifications = mysqli_query($conn, "
    SELECT *
    FROM notifications
    WHERE user_id='$user_id'
    ORDER BY id DESC
");

mysqli_query($conn, "
    UPDATE notifications
    SET is_read=1
    WHERE user_id='$user_id'
");
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
    <title>Notifikasi Saya</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="soft-bg-pattern buyer-panel-page buyer-notifications-page">

<?php include "../layouts/buyer-navbar.php"; ?>

<section class="section-wrap" style="padding-top:40px;">

    <div style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;flex-wrap:wrap;margin-bottom:30px;">
        <div>
            <p style="font-weight:900;color:#0284c7;margin-bottom:8px;">
                Pusat Notifikasi
            </p>

            <h1 class="page-title">
                Notifikasi Saya
            </h1>

            <p class="page-subtitle">
                Informasi terbaru tentang pesanan, pembayaran, dan delivery laundry kamu.
            </p>
        </div>

        <a href="orders.php" class="modern-btn">
            Lihat Pesanan
        </a>
    </div>

    <div style="display:flex;flex-direction:column;gap:18px;">

        <?php if ($notifications && mysqli_num_rows($notifications) > 0) : ?>

            <?php while ($notif = mysqli_fetch_assoc($notifications)) : ?>

                <div class="modern-card" style="padding:24px;display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:start;">

                    <div style="width:54px;height:54px;border-radius:20px;background:linear-gradient(135deg,#38bdf8,#0284c7);display:flex;align-items:center;justify-content:center;color:white;">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/>
                        </svg>
                    </div>

                    <div>
                        <h2 style="font-size:22px;font-weight:900;color:#0369a1;margin-bottom:8px;">
                            <?= htmlspecialchars($notif['title']); ?>
                        </h2>

                        <p style="color:#64748b;line-height:1.8;">
                            <?= nl2br(htmlspecialchars($notif['message'])); ?>
                        </p>

                        <p style="color:#94a3b8;margin-top:10px;font-size:14px;font-weight:700;">
                            <?= !empty($notif['created_at']) ? date('d M Y H:i', strtotime($notif['created_at'])) : '-'; ?>
                        </p>
                    </div>

                    <div>
                        <?php if ((int) $notif['is_read'] === 0) : ?>
                            <span class="status-pill" style="background:#fef3c7;color:#92400e;">Baru</span>
                        <?php else : ?>
                            <span class="status-pill" style="background:#f1f5f9;color:#334155;">Dibaca</span>
                        <?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else : ?>

            <div class="modern-card" style="padding:45px;text-align:center;">
                <div style="width:80px;height:80px;border-radius:28px;background:#e0f2fe;color:#0284c7;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;">
                    <svg width="42" height="42" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/>
                    </svg>
                </div>

                <h2 style="font-size:32px;font-weight:900;color:#0369a1;margin-bottom:10px;">
                    Belum Ada Notifikasi
                </h2>

                <p style="color:#64748b;margin-bottom:22px;">
                    Notifikasi pesanan laundry kamu akan tampil di sini.
                </p>

                <a href="create-order.php" class="modern-btn">
                    Buat Pesanan
                </a>
            </div>

        <?php endif; ?>

    </div>

</section>

<style>
@media (max-width: 700px) {
    .modern-card[style*="grid-template-columns:auto 1fr auto"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>