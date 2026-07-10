<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
?>

<aside class="dashboard-sidebar">

    <div class="sidebar-brand">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-weight:900;">
                L
            </div>

            <div>
                <h1><?= $role === 'petugas' ? 'Petugas' : 'Seller'; ?></h1>
                <p><?= $role === 'petugas' ? 'Operasional Laundry' : 'Mitra Laundry'; ?></p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">

        <?php if ($role === 'mitra' || $role === 'seller') : ?>

            <a href="dashboard.php" class="sidebar-link" style="<?= $currentPage == 'dashboard.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Dashboard
            </a>

            <a href="orders.php" class="sidebar-link" style="<?= $currentPage == 'orders.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Pesanan
            </a>

            <a href="services.php" class="sidebar-link" style="<?= $currentPage == 'services.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Layanan
            </a>

            <a href="staff.php" class="sidebar-link" style="<?= $currentPage == 'staff.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Petugas
            </a>

        <?php endif; ?>

        <?php if ($role === 'petugas') : ?>

            <a href="petugas-dashboard.php" class="sidebar-link" style="<?= $currentPage == 'petugas-dashboard.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Dashboard
            </a>

            <a href="petugas-orders.php" class="sidebar-link" style="<?= $currentPage == 'petugas-orders.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Pesanan
            </a>

            <a href="petugas-tasks.php" class="sidebar-link" style="<?= $currentPage == 'petugas-tasks.php' ? 'background:rgba(255,255,255,.22);' : ''; ?>">
                Tugas
            </a>

        <?php endif; ?>

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