<?php

if (defined('BUYER_NAVBAR_RENDERED')) {
    return;
}

define('BUYER_NAVBAR_RENDERED', true);

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

$baseUrl = appBaseUrl();

$currentPage = basename(
    parse_url(
        $_SERVER['REQUEST_URI'] ?? '',
        PHP_URL_PATH
    ) ?: ($_SERVER['PHP_SELF'] ?? '')
);

/*
|--------------------------------------------------------------------------
| SESSION PENGGUNA
|--------------------------------------------------------------------------
*/

$sessionUser = [];

if (
    isset($_SESSION['user']) &&
    is_array($_SESSION['user'])
) {
    $sessionUser = $_SESSION['user'];
}

$userId =
    $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? $sessionUser['id']
    ?? null;

$userRole =
    $_SESSION['role']
    ?? $_SESSION['user_role']
    ?? $sessionUser['role']
    ?? '';

$userName =
    $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['fullname']
    ?? $sessionUser['name']
    ?? 'Pengguna Laundry';

$userRole = strtolower(
    trim((string) $userRole)
);

if ($userRole === 'pelanggan') {
    $userRole = 'buyer';
}

if ($userRole === 'customer') {
    $userRole = 'buyer';
}

if ($userRole === 'mitra') {
    $userRole = 'seller';
}

$isLoggedIn = !empty($userId);
$isBuyer = $isLoggedIn && $userRole === 'buyer';

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/

$homeUrl =
    $baseUrl
    . '/src/views/public/home.php';

$orderUrl =
    $baseUrl
    . '/src/views/buyer/create-order.php';

$statusUrl =
    $baseUrl
    . '/src/views/buyer/orders.php';

$notificationUrl =
    $baseUrl
    . '/src/views/buyer/notifications.php';

$complaintUrl =
    $baseUrl
    . '/src/views/buyer/complaints.php';

$loginUrl =
    $baseUrl
    . '/src/views/public/login.php';

$registerUrl =
    $baseUrl
    . '/src/views/public/register.php';

$logoutUrl =
    $baseUrl
    . '/src/views/public/logout.php';

/*
|--------------------------------------------------------------------------
| PANEL BERDASARKAN ROLE
|--------------------------------------------------------------------------
*/

$panelUrl = '';
$panelLabel = '';

switch ($userRole) {
    case 'buyer':
        $panelUrl =
            $baseUrl
            . '/src/views/buyer/dashboard.php';

        $panelLabel = 'Panel Pelanggan';
        break;

    case 'admin':
        $panelUrl =
            $baseUrl
            . '/src/views/admin/dashboard.php';

        $panelLabel = 'Panel Admin';
        break;

    case 'seller':
        $panelUrl =
            $baseUrl
            . '/src/views/seller/dashboard.php';

        $panelLabel = 'Panel Seller';
        break;

    case 'petugas':
        $panelUrl =
            $baseUrl
            . '/src/views/seller/petugas-dashboard.php';

        $panelLabel = 'Panel Petugas';
        break;

    case 'customer_service':
        $panelUrl =
            $baseUrl
            . '/src/views/customer_service/dashboard.php';

        $panelLabel = 'Panel Customer Service';
        break;
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$activeMenu = static function (
    array $pages
) use (
    $currentPage
): string {
    return in_array(
        $currentPage,
        $pages,
        true
    )
        ? 'buyer-nav-active'
        : '';
};

/*
|--------------------------------------------------------------------------
| NOTIFIKASI BUYER
|--------------------------------------------------------------------------
*/

$unreadNotifications = 0;

if (
    $isBuyer &&
    isset($conn) &&
    $conn instanceof mysqli
) {
    $notificationStatement = mysqli_prepare(
        $conn,
        "
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = ?
              AND is_read = 0
        "
    );

    if ($notificationStatement) {
        $safeUserId = (int) $userId;

        mysqli_stmt_bind_param(
            $notificationStatement,
            'i',
            $safeUserId
        );

        mysqli_stmt_execute(
            $notificationStatement
        );

        $notificationResult =
            mysqli_stmt_get_result(
                $notificationStatement
            );

        if ($notificationResult) {
            $notificationRow =
                mysqli_fetch_assoc(
                    $notificationResult
                );

            $unreadNotifications = (int) (
                $notificationRow['total']
                ?? 0
            );
        }

        mysqli_stmt_close(
            $notificationStatement
        );
    }
}

?>

<style>
    :root {
        --buyer-primary: #0284c7;
        --buyer-secondary: #0ea5e9;
        --buyer-dark-blue: #075985;
        --buyer-dark: #0f172a;
        --buyer-muted: #64748b;
        --buyer-light: #e0f2fe;
        --buyer-soft: #f8fdff;
        --buyer-border: #bae6fd;
        --buyer-danger: #ef4444;
    }

    .buyer-navbar,
    .buyer-navbar * {
        box-sizing: border-box;
    }

    .buyer-navbar {
        position: sticky;
        top: 0;
        z-index: 9999;
        width: 100%;
        border-bottom: 1px solid var(--buyer-border);
        background: rgba(255, 255, 255, 0.98);
        box-shadow:
            0 7px 25px
            rgba(2, 132, 199, 0.08);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .buyer-navbar-container {
        display: flex;
        width: min(
            calc(100% - 32px),
            1160px
        );
        min-height: 72px;
        margin-right: auto;
        margin-left: auto;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .buyer-navbar-brand {
        display: inline-flex;
        min-width: 0;
        flex: 0 0 auto;
        align-items: center;
        gap: 10px;
        color: var(--buyer-dark-blue);
        text-decoration: none;
    }

    .buyer-navbar-logo {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background:
            linear-gradient(
                135deg,
                var(--buyer-secondary),
                #2563eb
            );
        box-shadow:
            0 10px 23px
            rgba(2, 132, 199, 0.21);
        color: #ffffff;
    }

    .buyer-navbar-logo svg {
        width: 23px;
        height: 23px;
    }

    .buyer-navbar-name {
        overflow: hidden;
        color: var(--buyer-dark-blue);
        font-size: 18px;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.4px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .buyer-navbar-menu {
        display: flex;
        min-width: 0;
        flex: 1;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
    }

    .buyer-navbar-link,
    .buyer-navbar-role {
        display: inline-flex;
        min-height: 41px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 11px;
        border: 1px solid transparent;
        border-radius: 999px;
        color: var(--buyer-dark);
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
    }

    .buyer-navbar-link {
        transition:
            color 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease,
            transform 0.2s ease;
    }

    .buyer-navbar-link:hover,
    .buyer-navbar-link.buyer-nav-active {
        border-color: var(--buyer-border);
        background: var(--buyer-light);
        color: var(--buyer-dark-blue);
    }

    .buyer-navbar-link:hover {
        transform: translateY(-1px);
    }

    .buyer-navbar-link svg,
    .buyer-navbar-role svg {
        width: 17px;
        height: 17px;
        flex: 0 0 17px;
    }

    .buyer-navbar-role {
        border-color: var(--buyer-border);
        background: #f0f9ff;
        color: var(--buyer-dark-blue);
        cursor: default;
    }

    .buyer-navbar-panel {
        border-color: #7dd3fc;
        background: #f0f9ff;
        color: var(--buyer-dark-blue);
    }

    .buyer-navbar-logout {
        margin-left: 3px;
        padding-right: 17px;
        padding-left: 17px;
        border-color: transparent;
        background:
            linear-gradient(
                135deg,
                var(--buyer-secondary),
                #2563eb
            );
        box-shadow:
            0 9px 21px
            rgba(2, 132, 199, 0.18);
        color: #ffffff;
    }

    .buyer-navbar-logout:hover {
        border-color: transparent;
        background:
            linear-gradient(
                135deg,
                #0284c7,
                #1d4ed8
            );
        color: #ffffff;
    }

    .buyer-navbar-badge {
        display: inline-flex;
        min-width: 19px;
        height: 19px;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        border-radius: 999px;
        background: var(--buyer-danger);
        color: #ffffff;
        font-size: 10px;
        font-weight: 900;
    }

    .buyer-navbar-user {
        display: none;
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--buyer-border);
        border-radius: 13px;
        background: var(--buyer-soft);
    }

    .buyer-navbar-user strong {
        display: block;
        color: var(--buyer-dark);
        font-size: 13px;
    }

    .buyer-navbar-user span {
        display: block;
        margin-top: 4px;
        color: var(--buyer-muted);
        font-size: 11px;
    }

    .buyer-navbar-toggle {
        display: none;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid var(--buyer-border);
        border-radius: 13px;
        background: var(--buyer-soft);
        color: var(--buyer-dark-blue);
    }

    .buyer-navbar-toggle svg {
        width: 22px;
        height: 22px;
    }

    @media screen and (max-width: 1080px) {
        .buyer-navbar-container {
            position: relative;
            width: calc(100% - 24px);
            min-height: 64px;
            padding: 10px 0;
        }

        .buyer-navbar-brand {
            max-width: calc(100% - 54px);
            flex: 1 1 auto;
            overflow: hidden;
        }

        .buyer-navbar-logo {
            width: 39px;
            height: 39px;
            flex-basis: 39px;
        }

        .buyer-navbar-name {
            max-width: 210px;
            font-size: 16px;
        }

        .buyer-navbar-toggle {
            display: inline-flex;
        }

        .buyer-navbar-menu {
            position: fixed;
            z-index: 10001;
            top: 64px;
            right: 10px;
            bottom: max(10px, env(safe-area-inset-bottom));
            left: 10px;
            display: none;
            width: auto;
            max-height: none;
            margin: 0;
            padding: 12px;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            overflow-y: auto;
            border: 1px solid var(--buyer-border);
            border-radius: 0 0 18px 18px;
            background: #ffffff;
            box-shadow:
                0 18px 35px
                rgba(15, 23, 42, 0.16);
        }

        .buyer-navbar-menu.buyer-navbar-menu-open {
            display: flex;
        }

        .buyer-navbar-user {
            display: block;
        }

        .buyer-navbar-link,
        .buyer-navbar-role {
            width: 100%;
            min-height: 46px;
            justify-content: flex-start;
            padding: 11px 14px;
            border-color: #e0f2fe;
            border-radius: 13px;
            background: var(--buyer-soft);
            font-size: 13px;
            white-space: normal;
        }

        .buyer-navbar-role {
            border-color: var(--buyer-border);
            background: var(--buyer-light);
        }

        .buyer-navbar-logout {
            position: sticky;
            z-index: 2;
            bottom: 0;
            flex: 0 0 auto;
            margin-top: auto;
            margin-left: 0;
            justify-content: center;
            border-color: transparent !important;
            background:
                linear-gradient(
                    135deg,
                    var(--buyer-secondary),
                    #2563eb
                ) !important;
            box-shadow:
                0 10px 24px
                rgba(2, 132, 199, 0.24);
            color: #ffffff !important;
        }

        .buyer-navbar-logout:hover,
        .buyer-navbar-logout:focus,
        .buyer-navbar-logout:active {
            border-color: transparent !important;
            background:
                linear-gradient(
                    135deg,
                    #0284c7,
                    #1d4ed8
                ) !important;
            color: #ffffff !important;
        }

        .buyer-navbar-logout svg,
        .buyer-navbar-logout span {
            color: #ffffff !important;
        }

        body.buyer-navbar-open {
            overflow: hidden !important;
        }
    }

    @media screen and (max-width: 380px) {
        .buyer-navbar-container {
            width: calc(100% - 20px);
        }

        .buyer-navbar-name {
            max-width: 150px;
            font-size: 15px;
        }
    }
</style>

<nav
    class="buyer-navbar"
    aria-label="Navigasi Laundry UMKM"
>
    <div class="buyer-navbar-container">

        <a
            href="<?= $escape($homeUrl); ?>"
            class="buyer-navbar-brand"
        >
            <span class="buyer-navbar-logo">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect
                        x="5"
                        y="2.5"
                        width="14"
                        height="19"
                        rx="3"
                    ></rect>

                    <path d="M8 6h.01"></path>
                    <path d="M11 6h5"></path>

                    <circle
                        cx="12"
                        cy="14"
                        r="4.2"
                    ></circle>

                    <path
                        d="M9.2 13.2c1.5 1.3 4.1 1.5 5.7.2"
                    ></path>
                </svg>
            </span>

            <span class="buyer-navbar-name">
                Laundry UMKM
            </span>
        </a>

        <button
            type="button"
            class="buyer-navbar-toggle"
            id="buyerNavbarToggle"
            aria-label="Buka menu"
            aria-expanded="false"
            aria-controls="buyerNavbarMenu"
        >
            <svg
                id="buyerNavbarToggleIcon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
            >
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
            </svg>
        </button>

        <div
            class="buyer-navbar-menu"
            id="buyerNavbarMenu"
        >

            <?php if ($isBuyer): ?>

                <div class="buyer-navbar-user">
                    <strong>
                        <?= $escape($userName); ?>
                    </strong>

                    <span>Pelanggan Laundry</span>
                </div>

                <a
                    href="<?= $escape($homeUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu(['home.php']); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 11.5 12 4l9 7.5"></path>
                        <path d="M5.5 10.5V20h13v-9.5"></path>
                        <path d="M9.5 20v-6h5v6"></path>
                    </svg>

                    <span>Home</span>
                </a>

                <a
                    href="<?= $escape($orderUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'create-order.php'
                        ]); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    >
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>

                    <span>Order Laundry</span>
                </a>

                <a
                    href="<?= $escape($statusUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'orders.php',
                            'order-detail.php',
                            'payment.php'
                        ]); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    >
                        <path d="M8 6h11"></path>
                        <path d="M8 12h11"></path>
                        <path d="M8 18h11"></path>
                        <path d="M3.5 6h.01"></path>
                        <path d="M3.5 12h.01"></path>
                        <path d="M3.5 18h.01"></path>
                    </svg>

                    <span>Pesanan</span>
                </a>

                <a
                    href="<?= $escape($notificationUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'notifications.php'
                        ]); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"
                        ></path>

                        <path d="M10 19h4"></path>
                    </svg>

                    <span>Notifikasi</span>

                    <?php if ($unreadNotifications > 0): ?>
                        <span class="buyer-navbar-badge">
                            <?= $unreadNotifications > 99
                                ? '99+'
                                : $unreadNotifications; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a
                    href="<?= $escape($complaintUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'complaints.php',
                            'complaint-detail.php',
                            'create-complaint.php'
                        ]); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                        ></path>

                        <path d="M8 9h8"></path>
                        <path d="M8 13h5"></path>
                    </svg>

                    <span>Keluhan</span>
                </a>

                <a
                    href="<?= $escape($panelUrl); ?>"
                    class="
                        buyer-navbar-link
                        buyer-navbar-panel
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    </svg>

                    <span>Panel Pelanggan</span>
                </a>

                <a
                    href="<?= $escape($logoutUrl); ?>"
                    class="
                        buyer-navbar-link
                        buyer-navbar-logout
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M10 17l5-5-5-5"></path>
                        <path d="M15 12H3"></path>

                        <path
                            d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"
                        ></path>
                    </svg>

                    <span>Logout</span>
                </a>

            <?php elseif ($isLoggedIn): ?>

                <div class="buyer-navbar-user">
                    <strong>
                        <?= $escape($userName); ?>
                    </strong>

                    <span>
                        <?= $escape(
                            $panelLabel !== ''
                                ? $panelLabel
                                : ucfirst($userRole)
                        ); ?>
                    </span>
                </div>

                <a
                    href="<?= $escape($homeUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu(['home.php']); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 11.5 12 4l9 7.5"></path>
                        <path d="M5.5 10.5V20h13v-9.5"></path>
                        <path d="M9.5 20v-6h5v6"></path>
                    </svg>

                    <span>Home</span>
                </a>

                <?php if ($panelUrl !== ''): ?>

                    <a
                        href="<?= $escape($panelUrl); ?>"
                        class="
                            buyer-navbar-link
                            buyer-navbar-panel
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
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

                        <span>
                            <?= $escape($panelLabel); ?>
                        </span>
                    </a>

                <?php endif; ?>

                <a
                    href="<?= $escape($logoutUrl); ?>"
                    class="
                        buyer-navbar-link
                        buyer-navbar-logout
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M10 17l5-5-5-5"></path>
                        <path d="M15 12H3"></path>

                        <path
                            d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"
                        ></path>
                    </svg>

                    <span>Logout</span>
                </a>

            <?php else: ?>

                <a
                    href="<?= $escape($homeUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu(['home.php']); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 11.5 12 4l9 7.5"></path>
                        <path d="M5.5 10.5V20h13v-9.5"></path>
                    </svg>

                    <span>Home</span>
                </a>

                <a
                    href="<?= $escape($loginUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu(['login.php']); ?>
                    "
                >
                    <span>Login</span>
                </a>

                <a
                    href="<?= $escape($registerUrl); ?>"
                    class="
                        buyer-navbar-link
                        buyer-navbar-logout
                    "
                >
                    <span>Daftar</span>
                </a>

            <?php endif; ?>

        </div>
    </div>
</nav>

<script>
(function () {
    const button =
        document.getElementById(
            'buyerNavbarToggle'
        );

    const menu =
        document.getElementById(
            'buyerNavbarMenu'
        );

    const icon =
        document.getElementById(
            'buyerNavbarToggleIcon'
        );

    if (!button || !menu || !icon) {
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

    function closeMenu() {
        menu.classList.remove(
            'buyer-navbar-menu-open'
        );

        button.setAttribute(
            'aria-expanded',
            'false'
        );

        icon.innerHTML = menuIcon;
        document.body.classList.remove('buyer-navbar-open');
    }

    button.addEventListener(
        'click',
        function (event) {
            event.stopPropagation();

            const opened =
                menu.classList.toggle(
                    'buyer-navbar-menu-open'
                );

            button.setAttribute(
                'aria-expanded',
                opened ? 'true' : 'false'
            );

            icon.innerHTML = opened
                ? closeIcon
                : menuIcon;

            document.body.classList.toggle(
                'buyer-navbar-open',
                opened
            );
        }
    );

    menu.querySelectorAll('a').forEach(
        function (link) {
            link.addEventListener(
                'click',
                closeMenu
            );
        }
    );

    document.addEventListener(
        'click',
        function (event) {
            if (
                !menu.contains(event.target) &&
                !button.contains(event.target)
            ) {
                closeMenu();
            }
        }
    );

    window.addEventListener(
        'resize',
        function () {
            if (window.innerWidth > 1080) {
                closeMenu();
            }
        }
    );
})();
</script>