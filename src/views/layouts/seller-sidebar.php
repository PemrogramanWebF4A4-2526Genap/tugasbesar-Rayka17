<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__
    . '/../../config/route-helper.php';

/*
|--------------------------------------------------------------------------
| DATA SESSION
|--------------------------------------------------------------------------
*/

$layoutUser = [];

if (
    isset($_SESSION['user'])
    && is_array($_SESSION['user'])
) {
    $layoutUser = $_SESSION['user'];
} elseif (
    isset($_SESSION['auth_user'])
    && is_array($_SESSION['auth_user'])
) {
    $layoutUser = $_SESSION['auth_user'];
}

$layoutRole = strtolower(
    trim(
        (string) (
            $layoutUser['role']
            ?? $_SESSION['role']
            ?? ''
        )
    )
);

$roleAliases = [
    'mitra' => 'seller',
    'penjual' => 'seller',
    'staff' => 'petugas',
    'kurir' => 'petugas'
];

$layoutRole =
    $roleAliases[$layoutRole]
    ?? $layoutRole;

$isPetugasLayout =
    $layoutRole === 'petugas';

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$layoutUrl = static function (
    string $path
): string {
    return appUrl($path);
};

$layoutEscape = static function (
    $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

/*
|--------------------------------------------------------------------------
| CURRENT PAGE
|--------------------------------------------------------------------------
*/

$currentLayoutPage = basename(
    parse_url(
        $_SERVER['REQUEST_URI'] ?? '',
        PHP_URL_PATH
    ) ?: ''
);

$layoutActive = static function (
    array $pages
) use ($currentLayoutPage): string {
    return in_array(
        $currentLayoutPage,
        $pages,
        true
    )
        ? 'active'
        : '';
};

/*
|--------------------------------------------------------------------------
| DATA TAMPILAN
|--------------------------------------------------------------------------
*/

$sessionName = trim(
    (string) (
        $layoutUser['name']
        ?? $_SESSION['name']
        ?? $_SESSION['user_name']
        ?? ''
    )
);

if ($isPetugasLayout) {
    $profileName = trim(
        (string) (
            $staff['fullname']
            ?? $sessionName
            ?? 'Petugas Laundry'
        )
    );

    $profileSubtext = trim(
        (string) (
            $staff['mitra_name']
            ?? 'Petugas Laundry'
        )
    );

    $panelTitle = 'Panel Petugas';

    $dashboardUrl = $layoutUrl(
        'src/views/seller/petugas-dashboard.php'
    );

    $logoLetter = 'P';
} else {
    $profileName = trim(
        (string) (
            $mitra['mitra_name']
            ?? $sessionName
            ?? 'Mitra Laundry'
        )
    );

    $profileSubtext =
        'Seller atau Mitra Laundry';

    $panelTitle = 'Panel Seller';

    $dashboardUrl = $layoutUrl(
        'src/views/seller/dashboard.php'
    );

    $logoLetter = 'S';
}

if ($profileName === '') {
    $profileName =
        $isPetugasLayout
            ? 'Petugas Laundry'
            : 'Mitra Laundry';
}

$homeUrl = $layoutUrl(
    'src/views/public/home.php'
);

$logoutUrl = $layoutUrl(
    'src/views/public/logout.php'
);

?>

<script>
(function () {
    if (
        !document.querySelector(
            'meta[name="viewport"]'
        )
    ) {
        const viewport =
            document.createElement('meta');

        viewport.name = 'viewport';

        viewport.content =
            'width=device-width, initial-scale=1, viewport-fit=cover';

        document.head.appendChild(
            viewport
        );
    }
})();
</script>

<style>
    :root {
        --seller-sidebar-width: 245px;
        --seller-topbar-height: 76px;

        --seller-sidebar-color: #043e57;
        --seller-sidebar-dark: #032f44;

        --seller-primary: #0284c7;
        --seller-primary-light: #0ea5e9;
        --seller-primary-dark: #075985;

        --seller-white: #ffffff;
        --seller-light-text: #dbeafe;
        --seller-border: #b6e4fa;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    html {
        width: 100%;
        min-height: 100%;
    }

    body.soft-bg-pattern {
        width: 100% !important;
        min-width: 0 !important;
        min-height: 100dvh !important;

        margin: 0 !important;

        overflow-x: hidden !important;

        font-family:
            "Segoe UI",
            Arial,
            Helvetica,
            sans-serif !important;
    }

    body.soft-bg-pattern button,
    body.soft-bg-pattern input,
    body.soft-bg-pattern select,
    body.soft-bg-pattern textarea {
        font-family:
            "Segoe UI",
            Arial,
            Helvetica,
            sans-serif !important;
    }

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    */

    body.soft-bg-pattern
    .seller-sidebar-fixed {
        position: fixed !important;

        z-index: 1550 !important;

        top: 0 !important;
        bottom: 0 !important;
        left: 0 !important;

        width:
            var(
                --seller-sidebar-width
            ) !important;

        height: 100vh !important;

        margin: 0 !important;

        padding:
            20px
            14px
            24px !important;

        overflow-x: hidden !important;
        overflow-y: auto !important;

        border: 0 !important;

        background:
            linear-gradient(
                180deg,
                var(--seller-sidebar-color)
                0%,
                var(--seller-sidebar-dark)
                100%
            ) !important;

        box-shadow:
            8px 0 30px
            rgba(15, 23, 42, 0.13) !important;

        color:
            var(--seller-white) !important;

        scrollbar-width: none;

        -ms-overflow-style: none;
    }

    .seller-sidebar-fixed::-webkit-scrollbar {
        display: none;

        width: 0;
        height: 0;
    }

    /*
    |--------------------------------------------------------------------------
    | BRAND
    |--------------------------------------------------------------------------
    */

    .seller-layout-brand {
        display: flex !important;

        min-width: 0;

        align-items: center !important;

        gap: 11px !important;

        padding:
            7px
            7px
            20px !important;

        color:
            var(--seller-white) !important;

        text-decoration: none !important;
    }

    .seller-layout-logo {
        display: inline-flex;

        width: 44px;
        height: 44px;

        flex: 0 0 44px;

        align-items: center;
        justify-content: center;

        border-radius: 13px;

        background:
            linear-gradient(
                135deg,
                var(--seller-primary-light),
                #2563eb
            );

        box-shadow:
            0 10px 22px
            rgba(14, 165, 233, 0.25);

        color:
            var(--seller-white);

        font-size: 15px;
        font-weight: 800;
    }

    .seller-layout-brand-text {
        min-width: 0;
    }

    .seller-layout-brand-text strong,
    .seller-layout-brand-text span {
        display: block;
    }

    .seller-layout-brand-text strong {
        overflow: hidden;

        color:
            var(--seller-white);

        font-size: 16px;
        font-weight: 800;
        line-height: 1.25;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .seller-layout-brand-text span {
        margin-top: 4px;

        color: #bae6fd;

        font-size: 11px;
        line-height: 1.35;
    }

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    .seller-layout-profile {
        margin-bottom: 16px;

        padding:
            14px
            13px;

        border:
            1px solid
            rgba(186, 230, 253, 0.20);

        border-radius: 15px;

        background:
            rgba(255, 255, 255, 0.08);
    }

    .seller-layout-profile strong,
    .seller-layout-profile span {
        display: block;
    }

    .seller-layout-profile strong {
        overflow: hidden;

        color:
            var(--seller-white);

        font-size: 13px;
        font-weight: 700;
        line-height: 1.4;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .seller-layout-profile span {
        margin-top: 5px;

        overflow: hidden;

        color: #bae6fd;

        font-size: 11px;
        line-height: 1.4;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /*
    |--------------------------------------------------------------------------
    | MENU
    |--------------------------------------------------------------------------
    */

    .seller-layout-menu {
        display: grid;

        gap: 7px;
    }

    .seller-layout-link {
        display: flex !important;

        width: 100% !important;
        min-height: 45px !important;

        align-items: center !important;

        gap: 11px !important;

        padding:
            11px
            12px !important;

        border:
            1px solid
            transparent !important;

        border-radius: 12px !important;

        background:
            transparent !important;

        color:
            var(--seller-light-text) !important;

        font-size: 13px !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;

        text-decoration: none !important;

        transition:
            background 0.2s ease,
            border-color 0.2s ease,
            color 0.2s ease !important;
    }

    .seller-layout-link:hover,
    .seller-layout-link.active {
        border-color:
            rgba(186, 230, 253, 0.14) !important;

        background:
            rgba(255, 255, 255, 0.14) !important;

        color:
            var(--seller-white) !important;
    }

    .seller-layout-icon {
        display: inline-flex;

        width: 19px;
        height: 19px;

        flex: 0 0 19px;

        align-items: center;
        justify-content: center;
    }

    .seller-layout-icon svg {
        width: 19px;
        height: 19px;

        fill: none;

        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .seller-layout-divider {
        height: 1px;

        margin:
            10px
            5px;

        background:
            rgba(186, 230, 253, 0.15);
    }

    .seller-layout-home {
        background:
            rgba(14, 165, 233, 0.11) !important;
    }

    .seller-layout-logout {
        margin-top: 2px;
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN
    |--------------------------------------------------------------------------
    */

    body.soft-bg-pattern
    .dashboard-main {
        display: block !important;

        width:
            calc(
                100% -
                var(--seller-sidebar-width)
            ) !important;

        min-width: 0 !important;
        min-height: 100vh !important;

        margin:
            0
            0
            0
            var(--seller-sidebar-width) !important;

        padding: 0 !important;

        overflow: visible !important;
    }

    .dashboard-main > section {
        min-width: 0;
    }

    /*
    |--------------------------------------------------------------------------
    | OVERLAY
    |--------------------------------------------------------------------------
    */

    body.soft-bg-pattern
    .mobile-overlay {
        position: fixed !important;

        z-index: 1500 !important;

        display: none;

        inset: 0 !important;

        background:
            rgba(15, 23, 42, 0.52) !important;

        backdrop-filter: blur(2px);

        -webkit-backdrop-filter: blur(2px);
    }

    body.soft-bg-pattern
    .mobile-overlay.active,
    body.soft-bg-pattern
    .mobile-overlay.open {
        display: block !important;
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media screen and (max-width: 1024px) {
        body.soft-bg-pattern
        .seller-sidebar-fixed {
            width:
                min(
                    285px,
                    86vw
                ) !important;

            padding:
                88px
                14px
                24px !important;

            transform:
                translateX(-105%);

            transition:
                transform
                0.25s ease !important;
        }

        body.soft-bg-pattern
        .seller-sidebar-fixed.open {
            transform:
                translateX(0);
        }

        body.soft-bg-pattern
        .dashboard-main {
            width: 100% !important;

            margin-left: 0 !important;
        }

        body.sidebar-open {
            overflow: hidden !important;
        }
    }
</style>

<aside
    class="seller-sidebar-fixed"
    id="sellerSidebar"
>
    <a
        href="<?= $layoutEscape(
            $dashboardUrl
        ); ?>"
        class="seller-layout-brand"
    >
        <span class="seller-layout-logo">
            <?= $layoutEscape(
                $logoLetter
            ); ?>
        </span>

        <span class="seller-layout-brand-text">
            <strong>
                Laundry UMKM
            </strong>

            <span>
                <?= $layoutEscape(
                    $panelTitle
                ); ?>
            </span>
        </span>
    </a>

    <section class="seller-layout-profile">
        <strong>
            <?= $layoutEscape(
                $profileName
            ); ?>
        </strong>

        <span>
            <?= $layoutEscape(
                $profileSubtext
            ); ?>
        </span>
    </section>

    <nav class="seller-layout-menu">

        <?php if (
            $isPetugasLayout
        ): ?>

            <a
                href="<?= $layoutEscape(
                    $layoutUrl(
                        'src/views/seller/petugas-dashboard.php'
                    )
                ); ?>"
                class="
                    seller-layout-link
                    <?= $layoutActive([
                        'petugas-dashboard.php'
                    ]); ?>
                "
            >
                <span class="seller-layout-icon">
                    <svg viewBox="0 0 24 24">
                        <rect
                            x="3"
                            y="3"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>

                        <rect
                            x="14"
                            y="3"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>

                        <rect
                            x="3"
                            y="14"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>

                        <rect
                            x="14"
                            y="14"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>
                    </svg>
                </span>

                <span>
                    Dashboard Petugas
                </span>
            </a>

            <a
                href="<?= $layoutEscape(
                    $layoutUrl(
                        'src/views/seller/petugas-tasks.php'
                    )
                ); ?>"
                class="
                    seller-layout-link
                    <?= $layoutActive([
                        'petugas-tasks.php',
                        'petugas-task.php'
                    ]); ?>
                "
            >
                <span class="seller-layout-icon">
                    <svg viewBox="0 0 24 24">
                        <circle
                            cx="12"
                            cy="7"
                            r="4"
                        ></circle>

                        <path
                            d="M4 21a8 8 0 0 1 16 0"
                        ></path>

                        <path
                            d="m17 11 2 2 4-4"
                        ></path>
                    </svg>
                </span>

                <span>
                    Tugas Pickup dan Delivery
                </span>
            </a>

        <?php else: ?>

            <a
                href="<?= $layoutEscape(
                    $layoutUrl(
                        'src/views/seller/dashboard.php'
                    )
                ); ?>"
                class="
                    seller-layout-link
                    <?= $layoutActive([
                        'dashboard.php'
                    ]); ?>
                "
            >
                <span class="seller-layout-icon">
                    <svg viewBox="0 0 24 24">
                        <rect
                            x="3"
                            y="3"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>

                        <rect
                            x="14"
                            y="3"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>

                        <rect
                            x="3"
                            y="14"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>

                        <rect
                            x="14"
                            y="14"
                            width="7"
                            height="7"
                            rx="1"
                        ></rect>
                    </svg>
                </span>

                <span>
                    Dashboard
                </span>
            </a>

            <a
                href="<?= $layoutEscape(
                    $layoutUrl(
                        'src/views/seller/services.php'
                    )
                ); ?>"
                class="
                    seller-layout-link
                    <?= $layoutActive([
                        'services.php'
                    ]); ?>
                "
            >
                <span class="seller-layout-icon">
                    <svg viewBox="0 0 24 24">
                        <circle
                            cx="12"
                            cy="12"
                            r="4"
                        ></circle>

                        <path d="M12 2v4"></path>
                        <path d="M12 18v4"></path>
                        <path d="M2 12h4"></path>
                        <path d="M18 12h4"></path>
                    </svg>
                </span>

                <span>
                    Layanan Laundry
                </span>
            </a>

            <a
                href="<?= $layoutEscape(
                    $layoutUrl(
                        'src/views/seller/orders.php'
                    )
                ); ?>"
                class="
                    seller-layout-link
                    <?= $layoutActive([
                        'orders.php'
                    ]); ?>
                "
            >
                <span class="seller-layout-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M8 6h12"></path>
                        <path d="M8 12h12"></path>
                        <path d="M8 18h12"></path>

                        <path d="M3 6h.01"></path>
                        <path d="M3 12h.01"></path>
                        <path d="M3 18h.01"></path>
                    </svg>
                </span>

                <span>
                    Pesanan Masuk
                </span>
            </a>

            <a
                href="<?= $layoutEscape(
                    $layoutUrl(
                        'src/views/seller/staff.php'
                    )
                ); ?>"
                class="
                    seller-layout-link
                    <?= $layoutActive([
                        'staff.php'
                    ]); ?>
                "
            >
                <span class="seller-layout-icon">
                    <svg viewBox="0 0 24 24">
                        <circle
                            cx="12"
                            cy="7"
                            r="4"
                        ></circle>

                        <path
                            d="M4 21a8 8 0 0 1 16 0"
                        ></path>
                    </svg>
                </span>

                <span>
                    Kelola Petugas
                </span>
            </a>

        <?php endif; ?>

        <div class="seller-layout-divider"></div>

        <a
            href="<?= $layoutEscape(
                $homeUrl
            ); ?>"
            class="
                seller-layout-link
                seller-layout-home
            "
        >
            <span class="seller-layout-icon">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M3 11.5 12 4l9 7.5"
                    ></path>

                    <path
                        d="M5.5 10.5V20h13v-9.5"
                    ></path>

                    <path
                        d="M9.5 20v-6h5v6"
                    ></path>
                </svg>
            </span>

            <span>
                Halaman Utama
            </span>
        </a>

        <a
            href="<?= $layoutEscape(
                $logoutUrl
            ); ?>"
            class="
                seller-layout-link
                seller-layout-logout
            "
        >
            <span class="seller-layout-icon">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M10 17l5-5-5-5"
                    ></path>

                    <path d="M15 12H3"></path>

                    <path
                        d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"
                    ></path>
                </svg>
            </span>

            <span>
                Logout
            </span>
        </a>

    </nav>
</aside>

<script>
(function () {
    const sidebar = document.getElementById('sellerSidebar');

    function getOverlay() {
        return document.querySelector('body.seller-panel-page .mobile-overlay');
    }

    function getToggle() {
        return document.getElementById('sellerSidebarToggle');
    }

    function sync(opened) {
        const toggle = getToggle();

        if (toggle) {
            toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
            toggle.setAttribute(
                'aria-label',
                opened ? 'Tutup menu panel' : 'Buka menu panel'
            );
        }
    }

    function openSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.add('open');

        const overlay = getOverlay();
        if (overlay) {
            overlay.classList.add('active');
        }

        document.body.classList.add('sidebar-open');
        sync(true);
    }

    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('open');
        }

        const overlay = getOverlay();
        if (overlay) {
            overlay.classList.remove('active', 'open', 'show');
        }

        document.body.classList.remove('sidebar-open');
        sync(false);
    }

    function toggleSidebar() {
        if (!sidebar) {
            return;
        }

        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    window.LaundrySellerSidebar = {
        open: openSidebar,
        close: closeSidebar,
        toggle: toggleSidebar
    };

    /* Compatibility for older inline calls. */
    window.openSidebar = openSidebar;
    window.closeSidebar = closeSidebar;
    window.toggleSidebar = toggleSidebar;

    function bindOverlay() {
        const overlay = getOverlay();
        if (overlay && overlay.dataset.drawerBound !== 'true') {
            overlay.dataset.drawerBound = 'true';
            overlay.addEventListener('click', closeSidebar);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindOverlay);
    } else {
        bindOverlay();
    }

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function () {
        bindOverlay();

        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
})();
</script>