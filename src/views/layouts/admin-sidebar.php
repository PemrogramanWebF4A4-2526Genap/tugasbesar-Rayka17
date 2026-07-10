<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="dashboard-sidebar">

    <div class="sidebar-brand">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-weight:900;">
                A
            </div>

            <div>
                <h1>Admin</h1>
                <p>Panel Laundry</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">

        <a href="dashboard.php" class="sidebar-link" style="<?= $currentPage == 'dashboard.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Dashboard
        </a>

        <a href="users.php" class="sidebar-link" style="<?= $currentPage == 'users.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Pengguna
        </a>

        <a href="partners.php" class="sidebar-link" style="<?= $currentPage == 'partners.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Seller
        </a>

        <a href="staff.php" class="sidebar-link" style="<?= $currentPage == 'staff.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Petugas
        </a>

        <a href="services.php" class="sidebar-link" style="<?= $currentPage == 'services.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Layanan
        </a>

        <a href="orders.php" class="sidebar-link" style="<?= $currentPage == 'orders.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Pesanan
        </a>

        <a href="../customer_service/complaints.php" class="sidebar-link">
            Keluhan
        </a>

    </nav>

    <div class="sidebar-footer">
        <a href="../public/home.php" class="sidebar-link">
            Home
        </a>

        <a href="../public/logout.php" class="sidebar-link" style="background:rgba(255,255,255,.18);">
            Logout
        </a>
    </div>

</aside>