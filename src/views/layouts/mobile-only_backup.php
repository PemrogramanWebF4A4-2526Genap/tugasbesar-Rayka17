<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| BASE URL PROJECT
|--------------------------------------------------------------------------
*/

$scriptName = str_replace(
    '\\',
    '/',
    $_SERVER['SCRIPT_NAME'] ?? ''
);

$srcPosition = strpos(
    $scriptName,
    '/src/'
);

$baseUrl = $srcPosition !== false
    ? substr($scriptName, 0, $srcPosition)
    : '';

/*
|--------------------------------------------------------------------------
| HALAMAN AKTIF
|--------------------------------------------------------------------------
*/

$requestPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '',
    PHP_URL_PATH
);

$currentPage = basename(
    $requestPath ?: ($_SERVER['PHP_SELF'] ?? '')
);

/*
|--------------------------------------------------------------------------
| NORMALISASI SESSION
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
    ?? 'Pelanggan Laundry';

$userRole = strtolower(
    trim((string) $userRole)
);

$isLoggedIn = !empty($userId);

$isBuyer = $isLoggedIn && (
    $userRole === ''
    || in_array(
        $userRole,
        [
            'buyer',
            'pelanggan',
            'customer'
        ],
        true
    )
);

/*
|--------------------------------------------------------------------------
| URL NAVIGASI
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
        ? 'buyer-navbar-link-active'
        : '';
};

?>

<style>
    :root {
        --buyer-primary: #0284c7;
        --buyer-secondary: #0ea5e9;
        --buyer-blue-dark: #075985;
        --buyer-dark: #0f172a;
        --buyer-muted: #64748b;
        --buyer-light: #e0f2fe;
        --buyer-soft: #f8fdff;
        --buyer-border: #bae6fd;
        --buyer-white: #ffffff;
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
        gap: 18px;
    }

    /*
    |--------------------------------------------------------------------------
    | BRAND
    |--------------------------------------------------------------------------
    */

    .buyer-navbar-brand {
        display: inline-flex;
        min-width: 0;
        flex: 0 0 auto;
        align-items: center;
        gap: 10px;
        color: var(--buyer-blue-dark);
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
        color: var(--buyer-blue-dark);
        font-size: 19px;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.4px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /*
    |--------------------------------------------------------------------------
    | MENU
    |--------------------------------------------------------------------------
    */

    .buyer-navbar-menu {
        display: flex;
        min-width: 0;
        flex: 1;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
    }

    .buyer-navbar-link {
        position: relative;
        display: inline-flex;
        min-height: 41px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 12px;
        border: 1px solid transparent;
        border-radius: 999px;
        color: var(--buyer-dark);
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        transition:
            color 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease,
            transform 0.2s ease;
    }

    .buyer-navbar-link:hover {
        border-color: var(--buyer-border);
        background: var(--buyer-light);
        color: var(--buyer-blue-dark);
        transform: translateY(-1px);
    }

    .buyer-navbar-link-active {
        border-color: var(--buyer-border);
        background: var(--buyer-light);
        color: var(--buyer-blue-dark);
    }

    .buyer-navbar-link svg {
        width: 17px;
        height: 17px;
        flex: 0 0 17px;
    }

    .buyer-navbar-logout {
        margin-left: 4px;
        padding-right: 18px;
        padding-left: 18px;
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

    /*
    |--------------------------------------------------------------------------
    | USER MOBILE
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | TOGGLE MOBILE
    |--------------------------------------------------------------------------
    */

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
        color: var(--buyer-blue-dark);
    }

    .buyer-navbar-toggle:hover {
        background: var(--buyer-light);
    }

    .buyer-navbar-toggle svg {
        width: 22px;
        height: 22px;
    }

    /*
    |--------------------------------------------------------------------------
    | TABLET DAN HP
    |--------------------------------------------------------------------------
    */

    @media screen and (max-width: 900px) {
        .buyer-navbar-container {
            position: relative;
            width: calc(100% - 24px);
            min-height: 64px;
            padding: 10px 0;
            flex-wrap: nowrap;
            gap: 10px;
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
            min-width: 0;
            max-width: 200px;
            font-size: 16px;
        }

        .buyer-navbar-toggle {
            display: inline-flex;
        }

        .buyer-navbar-menu {
            position: absolute;
            z-index: 10001;
            top: calc(100% + 1px);
            right: 0;
            left: 0;
            display: none;
            width: 100%;
            max-height: calc(100vh - 80px);
            max-height: calc(100dvh - 80px);
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

        .buyer-navbar-link {
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

        .buyer-navbar-link svg {
            width: 18px;
            height: 18px;
            flex-basis: 18px;
        }

        .buyer-navbar-logout {
            margin-left: 0;
            justify-content: center;
            border-color: transparent;
            color: #ffffff;
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

    @supports (
        padding:
            max(0px)
    ) {
        .buyer-navbar {
            padding-top:
                env(
                    safe-area-inset-top
                );
        }
    }
</style>

<nav
    class="buyer-navbar"
    aria-label="Navigasi pelanggan"
>
    <div class="buyer-navbar-container">

        <a
            href="<?= $escape($homeUrl); ?>"
            class="buyer-navbar-brand"
            aria-label="Laundry UMKM"
        >
            <span class="buyer-navbar-logo">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
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
            aria-label="Buka menu navigasi"
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
                aria-hidden="true"
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

                    <span>
                        Pelanggan Laundry
                    </span>
                </div>

                <a
                    href="<?= $escape($homeUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'home.php'
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
                        aria-hidden="true"
                    >
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

                    <span>Home</span>
                </a>

                <a
                    href="<?= $escape($orderUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'create-order.php',
                            'order.php'
                        ]); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        aria-hidden="true"
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
                            'payment.php',
                            'pickup.php'
                        ]); ?>
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        aria-hidden="true"
                    >
                        <path d="M8 6h11"></path>
                        <path d="M8 12h11"></path>
                        <path d="M8 18h11"></path>
                        <path d="M3.5 6h.01"></path>
                        <path d="M3.5 12h.01"></path>
                        <path d="M3.5 18h.01"></path>
                    </svg>

                    <span>Status Cucian</span>
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
                        aria-hidden="true"
                    >
                        <path
                            d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"
                        ></path>

                        <path d="M10 19h4"></path>
                    </svg>

                    <span>Notifikasi</span>
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
                        aria-hidden="true"
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
                        aria-hidden="true"
                    >
                        <path
                            d="M10 17l5-5-5-5"
                        ></path>

                        <path
                            d="M15 12H3"
                        ></path>

                        <path
                            d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"
                        ></path>
                    </svg>

                    <span>Logout</span>
                </a>

            <?php elseif ($isLoggedIn): ?>

                <a
                    href="<?= $escape($homeUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'home.php'
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
                            d="M3 11.5 12 4l9 7.5"
                        ></path>

                        <path
                            d="M5.5 10.5V20h13v-9.5"
                        ></path>
                    </svg>

                    <span>Home</span>
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
                        <path
                            d="M10 17l5-5-5-5"
                        ></path>

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
                        <?= $activeMenu([
                            'home.php'
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
                            d="M3 11.5 12 4l9 7.5"
                        ></path>

                        <path
                            d="M5.5 10.5V20h13v-9.5"
                        ></path>
                    </svg>

                    <span>Home</span>
                </a>

                <a
                    href="<?= $escape($loginUrl); ?>"
                    class="
                        buyer-navbar-link
                        <?= $activeMenu([
                            'login.php'
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
                            d="M10 17l5-5-5-5"
                        ></path>

                        <path d="M15 12H3"></path>

                        <path
                            d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"
                        ></path>
                    </svg>

                    <span>Login</span>
                </a>

                <a
                    href="<?= $escape($registerUrl); ?>"
                    class="
                        buyer-navbar-link
                        buyer-navbar-logout
                        <?= $activeMenu([
                            'register.php'
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
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                        ></path>

                        <circle
                            cx="9"
                            cy="7"
                            r="4"
                        ></circle>

                        <path d="M19 8v6"></path>
                        <path d="M22 11h-6"></path>
                    </svg>

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

    function openMenu() {
        menu.classList.add(
            'buyer-navbar-menu-open'
        );

        button.setAttribute(
            'aria-expanded',
            'true'
        );

        button.setAttribute(
            'aria-label',
            'Tutup menu navigasi'
        );

        icon.innerHTML = closeIcon;
    }

    function closeMenu() {
        menu.classList.remove(
            'buyer-navbar-menu-open'
        );

        button.setAttribute(
            'aria-expanded',
            'false'
        );

        button.setAttribute(
            'aria-label',
            'Buka menu navigasi'
        );

        icon.innerHTML = menuIcon;
    }

    button.addEventListener(
        'click',
        function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (
                menu.classList.contains(
                    'buyer-navbar-menu-open'
                )
            ) {
                closeMenu();
            } else {
                openMenu();
            }
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

    document.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        }
    );

    window.addEventListener(
        'resize',
        function () {
            if (window.innerWidth > 900) {
                closeMenu();
            }
        }
    );
})();
</script>