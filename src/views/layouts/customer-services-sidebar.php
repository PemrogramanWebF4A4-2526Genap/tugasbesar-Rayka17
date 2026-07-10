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
                CS
            </div>

            <div>
                <h1>Customer Service</h1>
                <p>Panel Keluhan</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">

        <a href="dashboard.php" class="sidebar-link" style="<?= $currentPage == 'dashboard.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Dashboard
        </a>

        <a href="complaints.php" class="sidebar-link" style="<?= $currentPage == 'complaints.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            Keluhan Pelanggan
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