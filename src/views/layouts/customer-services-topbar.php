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
                    Panel Customer Service
                </div>

                <div class="topbar-subtitle">
                    Tangani keluhan pelanggan dan masalah pesanan
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:12px;">
            <div class="clock-badge realtime-clock">
                Memuat waktu...
            </div>

            <div class="profile-badge">
                <?= strtoupper(substr($user['name'] ?? 'C', 0, 1)); ?>
            </div>
        </div>

    </div>
</header>