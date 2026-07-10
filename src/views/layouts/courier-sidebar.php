<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="dashboard-sidebar">

    <div class="sidebar-brand">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M3 13h13v5H3v-5Z"/>
                    <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M16 13l3-4h2v9h-5v-5Z"/>
                    <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M6 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm11 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                </svg>
            </div>

            <div>
                <h1>Kurir</h1>
                <p>Antar Jemput</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">

        <a href="dashboard.php" class="sidebar-link" style="<?= $currentPage == 'dashboard.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z"/>
            </svg>
            Dashboard
        </a>

        <a href="tasks.php" class="sidebar-link" style="<?= $currentPage == 'tasks.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
            </svg>
            Tugas Delivery
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