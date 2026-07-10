<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
?>

<header class="topbar-shell">
    <div class="topbar-inner">

        <div class="topbar-left">
            <button type="button" class="sidebar-toggle" onclick="toggleSidebar()">
                ☰
            </button>

            <div>
                <div class="topbar-title">
                    Panel Kurir
                </div>

                <div class="topbar-subtitle">
                    Kelola tugas jemput dan antar laundry
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <div class="clock-badge realtime-clock">
                Memuat waktu...
            </div>

            <div class="profile-badge">
                <?= strtoupper(substr($user['name'] ?? 'K', 0, 1)); ?>
            </div>
        </div>

    </div>
</header>