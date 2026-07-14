<?php

if (
    defined(
        'LAUNDRY_ROLE_SIDEBAR_RENDERED'
    )
) {
    return;
}

define(
    'LAUNDRY_ROLE_SIDEBAR_RENDERED',
    true
);

require_once __DIR__
    . '/../../config/auth.php';

$currentUser =
    laundry_auth_user();

$currentRole = laundry_normalize_role(
    $layoutRole
    ?? $currentUser['role']
    ?? ''
);

$currentPage = basename(
    parse_url(
        $_SERVER['REQUEST_URI']
        ?? '',
        PHP_URL_PATH
    ) ?: ''
);

$userName =
    $currentUser['name']
    ?? 'Pengguna Laundry';

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$menuActive = static function (
    array $pages
) use ($currentPage): string {
    return in_array(
        $currentPage,
        $pages,
        true
    )
        ? 'role-sidebar-active'
        : '';
};

$panelTitle = 'Panel Laundry';
$roleDescription = 'Pengguna Laundry';
$roleInitial = 'U';
$dashboardUrl = laundry_url(
    'src/views/public/home.php'
);
$menus = [];

switch ($currentRole) {
    case 'admin':
        $panelTitle = 'Panel Admin';
        $roleDescription = 'Administrator';
        $roleInitial = 'A';

        $dashboardUrl = laundry_url(
            'src/views/admin/dashboard.php'
        );

        $menus = [
            [
                'label' => 'Dashboard',
                'url' => laundry_url(
                    'src/views/admin/dashboard.php'
                ),
                'pages' => ['dashboard.php']
            ],
            [
                'label' => 'Kelola Pengguna',
                'url' => laundry_url(
                    'src/views/admin/users.php'
                ),
                'pages' => ['users.php']
            ],
            [
                'label' => 'Kelola Seller',
                'url' => laundry_url(
                    'src/views/admin/mitras.php'
                ),
                'pages' => ['mitras.php']
            ],
            [
                'label' => 'Kelola Petugas',
                'url' => laundry_url(
                    'src/views/admin/staff.php'
                ),
                'pages' => ['staff.php']
            ],
            [
                'label' => 'Layanan Laundry',
                'url' => laundry_url(
                    'src/views/admin/services.php'
                ),
                'pages' => ['services.php']
            ],
            [
                'label' => 'Seluruh Pesanan',
                'url' => laundry_url(
                    'src/views/admin/orders.php'
                ),
                'pages' => [
                    'orders.php',
                    'order-detail.php'
                ]
            ],
            [
                'label' => 'Keluhan Pelanggan',
                'url' => laundry_url(
                    'src/views/admin/complaints.php'
                ),
                'pages' => ['complaints.php']
            ]
        ];
        break;

    case 'seller':
        $panelTitle = 'Panel Seller';
        $roleDescription =
            'Seller atau Mitra Laundry';
        $roleInitial = 'S';

        $dashboardUrl = laundry_url(
            'src/views/seller/dashboard.php'
        );

        $menus = [
            [
                'label' => 'Dashboard',
                'url' => laundry_url(
                    'src/views/seller/dashboard.php'
                ),
                'pages' => ['dashboard.php']
            ],
            [
                'label' => 'Layanan Laundry',
                'url' => laundry_url(
                    'src/views/seller/services.php'
                ),
                'pages' => ['services.php']
            ],
            [
                'label' => 'Pesanan Masuk',
                'url' => laundry_url(
                    'src/views/seller/orders.php'
                ),
                'pages' => [
                    'orders.php',
                    'order-detail.php'
                ]
            ],
            [
                'label' => 'Kelola Petugas',
                'url' => laundry_url(
                    'src/views/seller/staff.php'
                ),
                'pages' => ['staff.php']
            ]
        ];
        break;

    case 'petugas':
        $panelTitle = 'Panel Petugas';
        $roleDescription = 'Petugas Laundry';
        $roleInitial = 'P';

        $dashboardUrl = laundry_url(
            'src/views/seller/petugas-dashboard.php'
        );

        $menus = [
            [
                'label' => 'Dashboard Petugas',
                'url' => laundry_url(
                    'src/views/seller/petugas-dashboard.php'
                ),
                'pages' => [
                    'petugas-dashboard.php'
                ]
            ],
            [
                'label' => 'Tugas Pickup dan Delivery',
                'url' => laundry_url(
                    'src/views/seller/petugas-tasks.php'
                ),
                'pages' => [
                    'petugas-tasks.php',
                    'petugas-task.php'
                ]
            ]
        ];
        break;

    case 'customer_service':
        $panelTitle =
            'Panel Customer Service';

        $roleDescription =
            'Customer Service';

        $roleInitial = 'CS';

        $dashboardUrl = laundry_url(
            'src/views/customer_service/dashboard.php'
        );

        $menus = [
            [
                'label' => 'Dashboard CS',
                'url' => laundry_url(
                    'src/views/customer_service/dashboard.php'
                ),
                'pages' => ['dashboard.php']
            ],
            [
                'label' => 'Keluhan Pelanggan',
                'url' => laundry_url(
                    'src/views/customer_service/complaints.php'
                ),
                'pages' => ['complaints.php']
            ]
        ];
        break;
}

?>

<style>
    :root {
        --role-sidebar-width: 245px;
        --role-navbar-height: 74px;
    }

    .role-sidebar,
    .role-sidebar * {
        box-sizing: border-box;
    }

    .role-sidebar {
        position: fixed;
        z-index: 9000;
        top: var(--role-navbar-height);
        bottom: 0;
        left: 0;
        width: var(--role-sidebar-width);
        padding: 18px 14px 25px;
        overflow-x: hidden;
        overflow-y: auto;
        background:
            linear-gradient(
                180deg,
                #06415a,
                #032f44
            );
        color: #ffffff;
        box-shadow:
            8px 0 25px
            rgba(15, 23, 42, 0.12);
    }

    .role-sidebar-header {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 8px 7px 18px;
        color: #ffffff;
        text-decoration: none;
    }

    .role-sidebar-logo {
        display: inline-flex;
        width: 43px;
        height: 43px;
        flex: 0 0 43px;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background:
            linear-gradient(
                135deg,
                #0ea5e9,
                #2563eb
            );
        font-size: 13px;
        font-weight: 900;
    }

    .role-sidebar-title {
        min-width: 0;
    }

    .role-sidebar-title strong,
    .role-sidebar-title span {
        display: block;
    }

    .role-sidebar-title strong {
        font-size: 15px;
    }

    .role-sidebar-title span {
        margin-top: 4px;
        color: #bae6fd;
        font-size: 11px;
    }

    .role-sidebar-profile {
        margin-bottom: 16px;
        padding: 14px 13px;
        border:
            1px solid
            rgba(186, 230, 253, 0.2);
        border-radius: 15px;
        background:
            rgba(255, 255, 255, 0.08);
    }

    .role-sidebar-profile strong,
    .role-sidebar-profile span {
        display: block;
    }

    .role-sidebar-profile strong {
        font-size: 13px;
    }

    .role-sidebar-profile span {
        margin-top: 5px;
        color: #bae6fd;
        font-size: 11px;
        line-height: 1.4;
    }

    .role-sidebar-menu {
        display: grid;
        gap: 7px;
    }

    .role-sidebar-link {
        display: flex;
        min-height: 44px;
        align-items: center;
        gap: 10px;
        padding: 11px 12px;
        border: 1px solid transparent;
        border-radius: 12px;
        color: #dbeafe;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
    }

    .role-sidebar-link:hover,
    .role-sidebar-link.role-sidebar-active {
        border-color:
            rgba(186, 230, 253, 0.14);
        background:
            rgba(255, 255, 255, 0.14);
        color: #ffffff;
    }

    .role-sidebar-icon {
        display: inline-flex;
        width: 19px;
        height: 19px;
        flex: 0 0 19px;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .role-sidebar-divider {
        height: 1px;
        margin: 10px 5px;
        background:
            rgba(186, 230, 253, 0.16);
    }

    .role-sidebar-home {
        background:
            rgba(14, 165, 233, 0.12);
    }

    .role-sidebar-:
            rgba(14, 165, 233, 0.12);
    }

    .role-sidebar-logout {
        background: #ffffff;
        color: #075985;
    }

    .role-sidebar-logout:hover {
        background: #e0f2fe;
        color: #075985;
    }

    .role-sidebar-toggle,
    .role-sidebar-overlay {
        display: none;
    }

    body.role-sidebar-body
    .main-content,
    body.role-sidebar-body
    .dashboard-content,
    body.role-sidebar-body
    .content-wrapper,
    body.role-sidebar-body
    .admin-content,
    body.role-sidebar-body
    .seller-content,
    body.role-sidebar-body
    .petugas-content,
    body.role-sidebar-body
    .customer-service-content,
    body.role-sidebar-body
    .page-content,
    body.role-sidebar-body
    .role-main-content {
        width:
            calc(
                100% - var(--role-sidebar-width)
            ) !important;
        max-width:
            calc(
                100% - var(--role-sidebar-width)
            ) !important;
        margin-left:
            var(--role-sidebar-width) !important;
    }

    @media screen and (max-width: 900px) {
        .role-sidebar {
            z-index: 10020;
            top: 64px;
            width: min(285px, 86vw);
            height: calc(100dvh - 64px);
            transform: translateX(-105%);
            transition: transform 0.25s ease;
        }

        .role-sidebar.role-sidebar-open {
            transform: translateX(0);
        }

        .role-sidebar-toggle {
            position: fixed;
            z-index: 10030;
            top: 76px;
            left: 12px;
            display: inline-flex;
            width: 44px;
            height: 44px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            border: 1px solid #bae6fd;
            border-radius: 13px;
            background: #ffffff;
            color: #075985;
            box-shadow:
                0 8px 22px
                rgba(15, 23, 42, 0.16);
        }

        .role-sidebar-overlay {
            position: fixed;
            z-index: 10010;
            display: none;
            top: 64px;
            right: 0;
            bottom: 0;
            left: 0;
            background:
                rgba(15, 23, 42, 0.52);
        }

        .role-sidebar-overlay.open {
            display: block;
        }

        body.role-sidebar-body
        .main-content,
        body.role-sidebar-body
        .dashboard-content,
        body.role-sidebar-body
        .content-wrapper,
        body.role-sidebar-body
        .admin-content,
        body.role-sidebar-body
        .seller-content,
        body.role-sidebar-body
        .petugas-content,
        body.role-sidebar-body
        .customer-service-content,
        body.role-sidebar-body
        .page-content,
        body.role-sidebar-body
        .role-main-content {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            padding-top: 58px !important;
        }
    }
</style>

<button
    type="button"
    class="role-sidebar-toggle"
    id="roleSidebarToggle"
    aria-label="Buka menu panel"
>
    ☰
</button>

<div
    class="role-sidebar-overlay"
    id="roleSidebarOverlay"
></div>

<aside
    class="sidebar role-sidebar"
    id="roleSidebar"
>
    <a
        href="<?= $escape($dashboardUrl); ?>"
        class="role-sidebar-header"
    >
        <span class="role-sidebar-logo">
            <?= $escape($roleInitial); ?>
        </span>

        <span class="role-sidebar-title">
            <strong>Laundry UMKM</strong>

            <span>
                <?= $escape($panelTitle); ?>
            </span>
        </span>
    </a>

    <div class="role-sidebar-profile">
        <strong>
            <?= $escape($userName); ?>
        </strong>

        <span>
            <?= $escape(
                $roleDescription
            ); ?>
        </span>
    </div>

    <nav class="role-sidebar-menu">

        <?php foreach (
            $menus as $index => $menu
        ): ?>

            <?php
            $icons = [
                '▦',
                '♙',
                '▤',
                '♧',
                '☼',
                '☷',
                '▣'
            ];
            ?>

            <a
                href="<?= $escape(
                    $menu['url']
                ); ?>"
                class="
                    role-sidebar-link
                    <?= $menuActive(
                        $menu['pages']
                    ); ?>
                "
            >
                <span class="role-sidebar-icon">
                    <?= $icons[
                        $index
                        % count($icons)
                    ]; ?>
                </span>

                <span>
                    <?= $escape(
                        $menu['label']
                    ); ?>
                </span>
            </a>

        <?php endforeach; ?>

        <div class="role-sidebar-divider"></div>

        <a
            href="<?= $escape(
                laundry_url(
                    'src/views/public/home.php'
                )
            ); ?>"
            class="
                role-sidebar-link
                role-sidebar-home
            "
        >
            <span class="role-sidebar-icon">
                ⌂
            </span>

            <span>Halaman Utama</span>
        </a>

        <a
            href="<?= $escape(
                laundry_url(
                    'src/views/public/logout.php'
                )
            ); ?>"
            class="
                role-sidebar-link
                role-sidebar-logout
            "
        >
            <span class="role-sidebar-icon">
                ↪
            </span>

            <span>Logout</span>
        </a>

    </nav>
</aside>

<script>
(function () {
    function initializeSidebar() {
        document.body.classList.add(
            'role-sidebar-body'
        );

        const toggle =
            document.getElementById(
                'roleSidebarToggle'
            );

        const sidebar =
            document.getElementById(
                'roleSidebar'
            );

        const overlay =
            document.getElementById(
                'roleSidebarOverlay'
            );

        if (
            !toggle
            || !sidebar
            || !overlay
        ) {
            return;
        }

        function closeSidebar() {
            sidebar.classList.remove(
                'role-sidebar-open'
            );

            overlay.classList.remove(
                'open'
            );

            toggle.textContent = '☰';

            document.body.style.overflow =
                '';
        }

        toggle.addEventListener(
            'click',
            function () {
                const opened =
                    sidebar.classList.toggle(
                        'role-sidebar-open'
                    );

                overlay.classList.toggle(
                    'open',
                    opened
                );

                toggle.textContent =
                    opened ? '✕' : '☰';

                document.body.style.overflow =
                    opened
                        ? 'hidden'
                        : '';
            }
        );

        overlay.addEventListener(
            'click',
            closeSidebar
        );

        sidebar
            .querySelectorAll('a')
            .forEach(function (link) {
                link.addEventListener(
                    'click',
                    function () {
                        if (
                            window.innerWidth
                            <= 900
                        ) {
                            closeSidebar();
                        }
                    }
                );
            });

        window.addEventListener(
            'resize',
            function () {
                if (
                    window.innerWidth > 900
                ) {
                    closeSidebar();
                }
            }
        );
    }

    if (
        document.readyState ===
        'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            initializeSidebar
        );
    } else {
        initializeSidebar();
    }
})();
</script>