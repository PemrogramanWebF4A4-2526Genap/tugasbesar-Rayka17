<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';

$title = "Panel Seller";
$subtitle = "Kelola layanan, pesanan, dan petugas";

if ($role === 'petugas') {
    $title = "Panel Petugas";
    $subtitle = "Kelola pesanan dan tugas laundry";
}
?>

<header class="topbar-shell">
    <div class="topbar-inner">

        <div class="topbar-left">
            <button type="button" class="sidebar-toggle" onclick="toggleSidebar()">
                ☰
            </button>

            <div>
                <div class="topbar-title">
                    <?= htmlspecialchars($title); ?>
                </div>

                <div class="topbar-subtitle">
                    <?= htmlspecialchars($subtitle); ?>
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <div class="clock-badge realtime-clock">
                Memuat waktu...
            </div>

            <div class="profile-badge">
                <?= strtoupper(substr($user['name'] ?? 'S', 0, 1)); ?>
            </div>
        </div>

    </div>
</header>