<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . "/../../config/database.php";

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;
$notifCount = 0;

if ($user && $role === 'buyer') {
    $user_id = $user['id'];

    $notifData = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id='$user_id'
        AND is_read=0
    "));

    $notifCount = $notifData['total'] ?? 0;
}
?>

<nav class="laundry-navbar">
    <div class="laundry-navbar-inner">

        <a href="../public/home.php" class="laundry-logo" style="display:flex;align-items:center;gap:12px;">
            <span class="logo-mark">
                <svg width="25" height="25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" d="M5 3h14v18H5V3Z"/>
                    <path stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" d="M8 6h.01M11 6h.01"/>
                    <path stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
                </svg>
            </span>
            <span>Laundry UMKM</span>
        </a>

        <div class="laundry-nav-menu">

            <a href="../public/home.php" class="laundry-nav-link">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10.5Z"/>
                </svg>
                Home
            </a>

            <?php if ($role === 'buyer') : ?>

                <a href="../buyer/create-order.php" class="laundry-nav-link">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    Order Laundry
                </a>

                <a href="../buyer/orders.php" class="laundry-nav-link">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                    </svg>
                    Status Cucian
                </a>

                <a href="../buyer/notifications.php" class="laundry-nav-link" style="position:relative;">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/>
                    </svg>
                    Notifikasi

                    <?php if ($notifCount > 0) : ?>
                        <span style="position:absolute;top:-6px;right:-8px;background:#ef4444;color:white;width:20px;height:20px;border-radius:999px;font-size:12px;display:flex;align-items:center;justify-content:center;">
                            <?= $notifCount; ?>
                        </span>
                    <?php endif; ?>
                </a>

            <?php elseif ($role === 'seller') : ?>

                <a href="../seller/dashboard.php" class="laundry-nav-link">
                    Dashboard Mitra
                </a>

            <?php elseif ($role === 'courier') : ?>

                <a href="../courier/dashboard.php" class="laundry-nav-link">
                    Dashboard Kurir
                </a>

            <?php elseif ($role === 'admin') : ?>

                <a href="../admin/dashboard.php" class="laundry-nav-link">
                    Admin Panel
                </a>

            <?php endif; ?>

            <?php if ($user) : ?>

                <span style="font-weight:800;color:#0369a1;">
                    <?= htmlspecialchars($user['name']); ?>
                </span>

                <a href="../public/logout.php" class="modern-btn">
                    Logout
                </a>

            <?php else : ?>

                <a href="../public/login.php" class="laundry-nav-link">
                    Login
                </a>

                <a href="../public/register.php" class="modern-btn">
                    Daftar Pelanggan
                </a>
                
                <a href="../buyer/complaints.php" class="nav-link">
                    Keluhan
                </a>

            <?php endif; ?>

        </div>

        <button type="button" class="mobile-menu-btn" onclick="document.getElementById('mobile-menu').classList.toggle('show')">
            ☰
        </button>

    </div>

    <div id="mobile-menu" class="mobile-menu-panel">

        <a href="../public/home.php" class="laundry-nav-link">
            Home
        </a>

        <?php if ($role === 'buyer') : ?>

            <a href="../buyer/create-order.php" class="laundry-nav-link">
                Order Laundry
            </a>

            <a href="../buyer/orders.php" class="laundry-nav-link">
                Status Cucian
            </a>

            <a href="../buyer/notifications.php" class="laundry-nav-link">
                Notifikasi <?= $notifCount > 0 ? '(' . $notifCount . ')' : ''; ?>
            </a>

        <?php elseif ($role === 'seller') : ?>

            <a href="../seller/dashboard.php" class="laundry-nav-link">
                Dashboard Mitra
            </a>

        <?php elseif ($role === 'courier') : ?>

            <a href="../courier/dashboard.php" class="laundry-nav-link">
                Dashboard Kurir
            </a>

        <?php elseif ($role === 'admin') : ?>

            <a href="../admin/dashboard.php" class="laundry-nav-link">
                Admin Panel
            </a>

        <?php endif; ?>

        <?php if ($user) : ?>

            <a href="../public/logout.php" class="modern-btn">
                Logout
            </a>

        <?php else : ?>

            <a href="../public/login.php" class="modern-btn-outline">
                Login
            </a>

            <a href="../public/register.php" class="modern-btn">
                Daftar
            </a>

        <?php endif; ?>

    </div>
</nav>