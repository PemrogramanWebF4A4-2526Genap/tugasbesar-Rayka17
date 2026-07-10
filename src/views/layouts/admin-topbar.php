<header class="topbar-shell">
    <div class="topbar-inner">
        <div class="topbar-left">
            <button type="button" onclick="toggleSidebar()" class="sidebar-toggle">
                ☰
            </button>

            <div>
                <div class="topbar-title">Admin Laundry</div>
                <div class="topbar-subtitle">Kelola layanan, pesanan, dan user laundry.</div>
            </div>
        </div>

        <div class="topbar-left">
            <span class="clock-badge realtime-clock"></span>

            <div class="text-right hidden sm:block">
                <p class="font-bold text-pink-700">
                    <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin Laundry'); ?>
                </p>
                <p class="text-sm text-pink-500">
                    Pengelola Laundry
                </p>
            </div>

            <div class="profile-badge">
                A
            </div>
        </div>
    </div>
</header>