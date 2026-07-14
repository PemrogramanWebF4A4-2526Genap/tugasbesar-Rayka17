<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__
    . '/../../config/route-helper.php';

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$adminSidebarUrl = static function (
    string $path
): string {
    return appUrl($path);
};

$adminSidebarEscape = static function (
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
| SESSION USER
|--------------------------------------------------------------------------
*/

$adminSidebarSessionUser = [];

if (
    isset($_SESSION['auth_user'])
    && is_array($_SESSION['auth_user'])
) {
    $adminSidebarSessionUser =
        $_SESSION['auth_user'];
} elseif (
    isset($_SESSION['user'])
    && is_array($_SESSION['user'])
) {
    $adminSidebarSessionUser =
        $_SESSION['user'];
}

$adminSidebarUserName =
    $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['fullname']
    ?? $_SESSION['login_name']
    ?? $adminSidebarSessionUser['name']
    ?? 'Admin Laundry';

$adminSidebarCurrentPage = basename(
    parse_url(
        $_SERVER['REQUEST_URI'] ?? '',
        PHP_URL_PATH
    ) ?: ''
);

/*
|--------------------------------------------------------------------------
| URL MENU
|--------------------------------------------------------------------------
*/

$adminSidebarDashboardUrl =
    $adminSidebarUrl(
        'src/views/admin/dashboard.php'
    );

$adminSidebarUsersUrl =
    $adminSidebarUrl(
        'src/views/admin/users.php'
    );

$adminSidebarMitrasUrl =
    $adminSidebarUrl(
        'src/views/admin/mitras.php'
    );

$adminSidebarStaffUrl =
    $adminSidebarUrl(
        'src/views/admin/staff.php'
    );

$adminSidebarServicesUrl =
    $adminSidebarUrl(
        'src/views/admin/services.php'
    );

$adminSidebarOrdersUrl =
    $adminSidebarUrl(
        'src/views/admin/orders.php'
    );

$adminSidebarComplaintsUrl =
    $adminSidebarUrl(
        'src/views/admin/complaints.php'
    );

$adminSidebarHomeUrl =
    $adminSidebarUrl(
        'src/views/public/home.php'
    );

$adminSidebarLogoutUrl =
    $adminSidebarUrl(
        'src/views/public/logout.php'
    );

/*
|--------------------------------------------------------------------------
| ACTIVE MENU
|--------------------------------------------------------------------------
*/

$adminSidebarActive = static function (
    array $pages
) use ($adminSidebarCurrentPage): string {
    return in_array(
        $adminSidebarCurrentPage,
        $pages,
        true
    )
        ? 'admin-sidebar-active'
        : '';
};

?>

<style>
    :root {
        --admin-sidebar-width: 245px;
        --admin-sidebar-primary: #0284c7;
        --admin-sidebar-secondary: #0ea5e9;
        --admin-sidebar-dark: #063b54;
        --admin-sidebar-darker: #032f44;
        --admin-sidebar-white: #ffffff;
        --admin-sidebar-text: #dbeafe;
        --admin-sidebar-border:
            rgba(186, 230, 253, 0.2);
    }

    .admin-sidebar-layout,
    .admin-sidebar-layout *,
    .admin-sidebar-overlay,
    .admin-sidebar-mobile-toggle {
        box-sizing: border-box;
        font-family:
            "Segoe UI",
            Arial,
            Helvetica,
            sans-serif;
    }

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR ADMIN
    |--------------------------------------------------------------------------
    */

    .admin-sidebar-layout {
        position: fixed;
        z-index: 1550;
        top: 0;
        bottom: 0;
        left: 0;

        width: var(--admin-sidebar-width);
        height: 100dvh;

        padding: 20px 14px 24px;

        overflow-x: hidden;
        overflow-y: auto;

        background:
            linear-gradient(
                180deg,
                var(--admin-sidebar-dark) 0%,
                var(--admin-sidebar-darker) 100%
            );

        color: var(--admin-sidebar-white);

        box-shadow:
            8px 0 30px
            rgba(15, 23, 42, 0.13);

        /*
        |--------------------------------------------------------------------------
        | HILANGKAN SCROLLBAR ADMIN SAJA
        |--------------------------------------------------------------------------
        */

        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .admin-sidebar-layout::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .admin-sidebar-layout::-webkit-scrollbar-track {
        display: none;
        background: transparent;
    }

    .admin-sidebar-layout::-webkit-scrollbar-thumb {
        display: none;
        background: transparent;
    }

    /*
    |--------------------------------------------------------------------------
    | BRAND
    |--------------------------------------------------------------------------
    */

    .admin-sidebar-brand {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 11px;

        padding: 7px 7px 20px;

        color: var(--admin-sidebar-white);
        text-decoration: none;
    }

    .admin-sidebar-logo {
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
                var(--admin-sidebar-secondary),
                #2563eb
            );

        box-shadow:
            0 10px 22px
            rgba(14, 165, 233, 0.24);

        color: var(--admin-sidebar-white);
        font-size: 15px;
        font-weight: 800;
    }

    .admin-sidebar-brand-content {
        min-width: 0;
    }

    .admin-sidebar-brand-content strong,
    .admin-sidebar-brand-content span {
        display: block;
    }

    .admin-sidebar-brand-content strong {
        overflow: hidden;

        color: var(--admin-sidebar-white);

        font-size: 16px;
        font-weight: 800;
        line-height: 1.25;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-sidebar-brand-content span {
        margin-top: 4px;

        color: #bae6fd;

        font-size: 11px;
        font-weight: 400;
        line-height: 1.3;
    }

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    .admin-sidebar-profile {
        margin-bottom: 16px;
        padding: 14px 13px;

        border:
            1px solid
            var(--admin-sidebar-border);

        border-radius: 15px;

        background:
            rgba(255, 255, 255, 0.08);
    }

    .admin-sidebar-profile strong,
    .admin-sidebar-profile span {
        display: block;
    }

    .admin-sidebar-profile strong {
        overflow: hidden;

        color: var(--admin-sidebar-white);

        font-size: 13px;
        font-weight: 700;
        line-height: 1.4;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .admin-sidebar-profile span {
        margin-top: 5px;

        color: #bae6fd;

        font-size: 11px;
        font-weight: 400;
    }

    /*
    |--------------------------------------------------------------------------
    | MENU
    |--------------------------------------------------------------------------
    */

    .admin-sidebar-menu {
        display: grid;
        gap: 7px;
    }

    .admin-sidebar-link {
        display: flex;

        width: 100%;
        min-height: 45px;

        align-items: center;
        gap: 11px;

        padding: 11px 12px;

        border: 1px solid transparent;
        border-radius: 12px;

        color: var(--admin-sidebar-text);

        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;

        text-decoration: none;

        transition:
            background 0.2s ease,
            border-color 0.2s ease,
            color 0.2s ease;
    }

    .admin-sidebar-link:hover,
    .admin-sidebar-link.admin-sidebar-active {
        border-color:
            rgba(186, 230, 253, 0.14);

        background:
            rgba(255, 255, 255, 0.14);

        color: var(--admin-sidebar-white);
    }

    .admin-sidebar-icon {
        display: inline-flex;

        width: 19px;
        height: 19px;
        flex: 0 0 19px;

        align-items: center;
        justify-content: center;
    }

    .admin-sidebar-icon svg {
        width: 19px;
        height: 19px;

        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .admin-sidebar-divider {
        height: 1px;
        margin: 10px 5px;

        background:
            rgba(186, 230, 253, 0.16);
    }

    .admin-sidebar-home {
        background:
            rgba(14, 165, 233, 0.1);
    }

    .admin-sidebar-logout {
        margin-top: 2px;

        background: var(--admin-sidebar-white);
        color: #075985;
    }

    .admin-sidebar-logout:hover {
        border-color: transparent;
        background: #e0f2fe;
        color: #075985;
    }

    /*
    |--------------------------------------------------------------------------
    | OVERLAY MOBILE
    |--------------------------------------------------------------------------
    */

    .admin-sidebar-overlay {
        position: fixed;
        z-index: 1500;

        display: none;

        inset: 0;

        background:
            rgba(15, 23, 42, 0.52);

        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    .admin-sidebar-overlay.open {
        display: block;
    }

    .admin-sidebar-mobile-toggle {
        display: none;
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE ADMIN
    |--------------------------------------------------------------------------
    */

    @media screen and (max-width: 1024px) {
        .admin-sidebar-layout {
            z-index: 1550;

            width: min(285px, 86vw);
            padding-top: 88px;

            transform: translateX(-105%);

            transition:
                transform 0.25s ease;
        }

        .admin-sidebar-layout.open {
            transform: translateX(0);
        }

        .admin-sidebar-mobile-toggle {
            display: none !important;
            position: fixed;
            z-index: 1450;

            top: 13px;
            left: 13px;

            width: 42px;
            height: 42px;

            cursor: pointer;

            align-items: center;
            justify-content: center;

            padding: 0;

            border: 1px solid #b6e4fa;
            border-radius: 12px;

            background: #ffffff;

            box-shadow:
                0 8px 22px
                rgba(15, 23, 42, 0.15);

            color: #075985;
        }

        .admin-sidebar-mobile-toggle svg {
            width: 22px;
            height: 22px;

            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
        }
    }
</style>

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>

<aside
    class="admin-sidebar-layout"
    id="adminSidebarLayout"
>
    <a
        href="<?= $adminSidebarEscape(
            $adminSidebarDashboardUrl
        ); ?>"
        class="admin-sidebar-brand"
    >
        <span class="admin-sidebar-logo">
            A
        </span>

        <span class="admin-sidebar-brand-content">
            <strong>
                Laundry UMKM
            </strong>

            <span>
                Panel Admin
            </span>
        </span>
    </a>

    <section class="admin-sidebar-profile">
        <strong>
            <?= $adminSidebarEscape(
                $adminSidebarUserName
            ); ?>
        </strong>

        <span>
            Administrator
        </span>
    </section>

    <nav class="admin-sidebar-menu">

        <a
            href="<?= $adminSidebarEscape(
                $adminSidebarDashboardUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'dashboard.php',
                    'index.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
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
            href="<?= $adminSidebarEscape(
                $adminSidebarUsersUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'users.php',
                    'user-detail.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
                <svg viewBox="0 0 24 24">
                    <circle
                        cx="9"
                        cy="7"
                        r="4"
                    ></circle>

                    <path
                        d="M2 21a7 7 0 0 1 14 0"
                    ></path>

                    <path
                        d="M17 3a4 4 0 0 1 0 8"
                    ></path>
                </svg>
            </span>

            <span>
                Kelola Pengguna
            </span>
        </a>

        <a
            href="<?= $adminSidebarEscape(
                $adminSidebarMitrasUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'mitras.php',
                    'partners.php',
                    'sellers.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M3 9l2-5h14l2 5"
                    ></path>

                    <path
                        d="M5 13v7h14v-7"
                    ></path>

                    <path
                        d="M9 20v-5h6v5"
                    ></path>
                </svg>
            </span>

            <span>
                Kelola Seller
            </span>
        </a>

        <a
            href="<?= $adminSidebarEscape(
                $adminSidebarStaffUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'staff.php',
                    'staff-detail.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
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

        <a
            href="<?= $adminSidebarEscape(
                $adminSidebarServicesUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'services.php',
                    'service-detail.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
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
            href="<?= $adminSidebarEscape(
                $adminSidebarOrdersUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'orders.php',
                    'order.php',
                    'order-detail.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
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
                Seluruh Pesanan
            </span>
        </a>

        <a
            href="<?= $adminSidebarEscape(
                $adminSidebarComplaintsUrl
            ); ?>"
            class="
                admin-sidebar-link
                <?= $adminSidebarActive([
                    'complaints.php',
                    'complaint-detail.php'
                ]); ?>
            "
        >
            <span class="admin-sidebar-icon">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                    ></path>

                    <path d="M8 9h8"></path>
                    <path d="M8 13h5"></path>
                </svg>
            </span>

            <span>
                Keluhan Pelanggan
            </span>
        </a>

        <div class="admin-sidebar-divider"></div>

        <a
            href="<?= $adminSidebarEscape(
                $adminSidebarHomeUrl
            ); ?>"
            class="
                admin-sidebar-link
                admin-sidebar-home
            "
        >
            <span class="admin-sidebar-icon">
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
            href="<?= $adminSidebarEscape(
                $adminSidebarLogoutUrl
            ); ?>"
            class="
                admin-sidebar-link
                admin-sidebar-logout
            "
        >
            <span class="admin-sidebar-icon">
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

<button
    type="button"
    class="admin-sidebar-mobile-toggle"
    id="adminSidebarMobileToggle"
    aria-label="Buka menu admin"
    aria-expanded="false"
>
    <svg
        id="adminSidebarToggleIcon"
        viewBox="0 0 24 24"
    >
        <path d="M4 7h16"></path>
        <path d="M4 12h16"></path>
        <path d="M4 17h16"></path>
    </svg>
</button>

<script>
(function () {
    const sidebar = document.getElementById('adminSidebarLayout');
    const overlay = document.getElementById('adminSidebarOverlay');
    const legacyToggle = document.getElementById('adminSidebarMobileToggle');
    const legacyIcon = document.getElementById('adminSidebarToggleIcon');

    if (!sidebar || !overlay) {
        return;
    }

    const menuIcon = `
        <path d="M4 7h16"></path>
        <path d="M4 12h16"></path>
        <path d="M4 17h16"></path>
    `;

    const closeIcon = `
        <path d="M6 6l12 12"></path>
        <path d="M18 6 6 18"></path>
    `;

    function syncButtons(opened) {
        document.querySelectorAll(
            '#adminTopbarSidebarToggle, #adminSidebarMobileToggle'
        ).forEach(function (button) {
            button.setAttribute(
                'aria-expanded',
                opened ? 'true' : 'false'
            );
        });

        if (legacyIcon) {
            legacyIcon.innerHTML = opened
                ? closeIcon
                : menuIcon;
        }
    }

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.classList.add('admin-sidebar-open');
        syncButtons(true);
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.classList.remove('admin-sidebar-open');
        syncButtons(false);
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    window.openAdminSidebar = openSidebar;
    window.closeAdminSidebar = closeSidebar;
    window.LaundryAdminSidebar = {
        open: openSidebar,
        close: closeSidebar,
        toggle: toggleSidebar
    };

    /* Compatibility for older inline calls. */
    window.openSidebar = openSidebar;
    window.closeSidebar = closeSidebar;
    window.toggleSidebar = toggleSidebar;

    if (legacyToggle) {
        legacyToggle.addEventListener('click', toggleSidebar);
    }

    overlay.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
})();
</script>