<?php
require_once __DIR__ . '/../../config/route-helper.php';
?>
<nav class="bg-white shadow-sm border-b sticky top-0 z-50">

    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

        <!-- LOGO -->
        <a href="<?= htmlspecialchars(appUrl('src/views/public/home.php'), ENT_QUOTES, 'UTF-8'); ?>">

            <h1 class="text-2xl font-bold text-[#292c41]">
                UMKM Marketplace
            </h1>

        </a>

        <!-- MENU -->
        <div class="flex items-center gap-4">

            <a
                href="<?= htmlspecialchars(appUrl('src/views/public/login.php'), ENT_QUOTES, 'UTF-8'); ?>"
                class="text-gray-700 hover:text-[#292c41] transition"
            >
                Login
            </a>

            <a
                href="<?= htmlspecialchars(appUrl('src/views/public/register.php'), ENT_QUOTES, 'UTF-8'); ?>"
                class="bg-[#292c41] text-white px-5 py-2 rounded-xl hover:opacity-90 transition"
            >
                Register
            </a>

        </div>

    </div>

</nav>