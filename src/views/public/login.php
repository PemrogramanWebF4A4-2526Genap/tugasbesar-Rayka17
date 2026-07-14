<?php

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/route-helper.php';

/*
|--------------------------------------------------------------------------
| BASE URL PROJECT
|--------------------------------------------------------------------------
*/

$baseUrl = appBaseUrl();

$projectRoot = dirname(__DIR__, 3);

/*
|--------------------------------------------------------------------------
| HELPER URL
|--------------------------------------------------------------------------
*/

function loginUrl(
    string $baseUrl,
    string $path
): string {
    return rtrim($baseUrl, '/')
        . '/'
        . ltrim($path, '/');
}

function loginPageExists(
    string $projectRoot,
    string $relativePath
): bool {
    $relativePath = str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        ltrim($relativePath, '/\\')
    );

    return is_file(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . $relativePath
    );
}

function loginFirstExistingPage(
    string $baseUrl,
    string $projectRoot,
    array $pages,
    string $fallback
): string {
    foreach ($pages as $page) {
        if (
            loginPageExists(
                $projectRoot,
                $page
            )
        ) {
            return loginUrl(
                $baseUrl,
                $page
            );
        }
    }

    return loginUrl(
        $baseUrl,
        $fallback
    );
}

function loginNormalizeRole(
    ?string $role
): string {
    $role = strtolower(
        trim((string) $role)
    );

    $aliases = [
        'mitra' => 'seller',
        'penjual' => 'seller',
        'pelanggan' => 'buyer',
        'customer' => 'buyer',
        'kurir' => 'petugas',
        'customer-service' => 'customer_service',
        'customer service' => 'customer_service',
        'customerservice' => 'customer_service',
        'cs' => 'customer_service'
    ];

    return $aliases[$role] ?? $role;
}

function loginDashboardUrl(
    string $role,
    string $baseUrl,
    string $projectRoot
): string {
    $role = loginNormalizeRole($role);

    switch ($role) {
        case 'admin':
            return loginFirstExistingPage(
                $baseUrl,
                $projectRoot,
                [
                    'src/views/admin/dashboard.php',
                    'src/views/admin/index.php'
                ],
                'src/views/public/home.php'
            );

        case 'seller':
            return loginFirstExistingPage(
                $baseUrl,
                $projectRoot,
                [
                    'src/views/seller/dashboard.php',
                    'src/views/mitra/dashboard.php',
                    'src/views/seller/index.php'
                ],
                'src/views/public/home.php'
            );

        case 'petugas':
            return loginFirstExistingPage(
                $baseUrl,
                $projectRoot,
                [
                    'src/views/seller/petugas-dashboard.php',
                    'src/views/seller/petugas_dashboard.php',
                    'src/views/petugas/dashboard.php',
                    'src/views/staff/dashboard.php'
                ],
                'src/views/public/home.php'
            );

        case 'customer_service':
            return loginFirstExistingPage(
                $baseUrl,
                $projectRoot,
                [
                    'src/views/customer_service/dashboard.php',
                    'src/views/customer-service/dashboard.php',
                    'src/views/cs/dashboard.php'
                ],
                'src/views/public/home.php'
            );

        case 'buyer':
            return loginFirstExistingPage(
                $baseUrl,
                $projectRoot,
                [
                    'src/views/public/home.php',
                    'src/views/buyer/dashboard.php',
                    'src/views/buyer/orders.php'
                ],
                'src/views/public/home.php'
            );

        default:
            return loginUrl(
                $baseUrl,
                'src/views/public/home.php'
            );
    }
}

function loginRedirect(string $url): void
{
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }

    echo '<script>';
    echo 'window.location.href = '
        . json_encode($url)
        . ';';
    echo '</script>';

    exit;
}

function loginEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| URL HALAMAN
|--------------------------------------------------------------------------
*/

$homeUrl = loginUrl(
    $baseUrl,
    'src/views/public/home.php'
);

$loginPageUrl = loginUrl(
    $baseUrl,
    'src/views/public/login.php'
);

$registerUrl = loginUrl(
    $baseUrl,
    'src/views/public/register.php'
);

$complaintLoginUrl =
    $loginPageUrl
    . '?redirect=complaints';

/*
|--------------------------------------------------------------------------
| PESAN
|--------------------------------------------------------------------------
*/

$errorMessage = '';
$successMessage = '';

if (
    isset($_GET['logout'])
    && $_GET['logout'] === '1'
) {
    $successMessage =
        'Logout berhasil. Silakan login kembali.';
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

/*
|--------------------------------------------------------------------------
| PROSES LOGIN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $csrfToken = (string) (
        $_POST['csrf_token']
        ?? ''
    );

    $sessionToken = (string) (
        $_SESSION['login_csrf']
        ?? ''
    );

    $redirectTarget = trim(
        (string) (
            $_POST['redirect']
            ?? ''
        )
    );

    if (
        $csrfToken === ''
        || $sessionToken === ''
        || !hash_equals(
            $sessionToken,
            $csrfToken
        )
    ) {
        $errorMessage =
            'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    } else {
        $email = strtolower(
            trim(
                (string) (
                    $_POST['email']
                    ?? ''
                )
            )
        );

        $passwordInput = (string) (
            $_POST['password']
            ?? ''
        );

        if (
            $email === ''
            || $passwordInput === ''
        ) {
            $errorMessage =
                'Email dan password wajib diisi.';
        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errorMessage =
                'Format email tidak valid.';
        } else {
            $statement = mysqli_prepare(
                $conn,
                "
                    SELECT
                        id,
                        name,
                        email,
                        password,
                        role,
                        mitra_id,
                        status
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                "
            );

            if (!$statement) {
                $errorMessage =
                    'Sistem login sedang bermasalah.';
            } else {
                mysqli_stmt_bind_param(
                    $statement,
                    's',
                    $email
                );

                mysqli_stmt_execute(
                    $statement
                );

                mysqli_stmt_store_result(
                    $statement
                );

                if (
                    mysqli_stmt_num_rows(
                        $statement
                    ) !== 1
                ) {
                    $errorMessage =
                        'Email atau password salah.';
                } else {
                    mysqli_stmt_bind_result(
                        $statement,
                        $databaseId,
                        $databaseName,
                        $databaseEmail,
                        $databasePassword,
                        $databaseRole,
                        $databaseMitraId,
                        $databaseStatus
                    );

                    mysqli_stmt_fetch(
                        $statement
                    );

                    $passwordValid =
                        password_verify(
                            $passwordInput,
                            (string) $databasePassword
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | DUKUNG PASSWORD LAMA TANPA HASH
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$passwordValid
                        && hash_equals(
                            (string) $databasePassword,
                            $passwordInput
                        )
                    ) {
                        $passwordValid = true;
                    }

                    if (!$passwordValid) {
                        $errorMessage =
                            'Email atau password salah.';
                    } elseif (
                        strtolower(
                            trim(
                                (string) $databaseStatus
                            )
                        ) !== 'active'
                    ) {
                        $errorMessage =
                            'Akun tidak aktif atau sedang diblokir.';
                    } else {
                        $normalizedRole =
                            loginNormalizeRole(
                                $databaseRole
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | CARI MITRA SELLER
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $normalizedRole === 'seller'
                            && empty($databaseMitraId)
                        ) {
                            $mitraStatement =
                                mysqli_prepare(
                                    $conn,
                                    "
                                        SELECT id
                                        FROM laundry_mitras
                                        WHERE user_id = ?
                                        LIMIT 1
                                    "
                                );

                            if ($mitraStatement) {
                                mysqli_stmt_bind_param(
                                    $mitraStatement,
                                    'i',
                                    $databaseId
                                );

                                mysqli_stmt_execute(
                                    $mitraStatement
                                );

                                mysqli_stmt_bind_result(
                                    $mitraStatement,
                                    $foundMitraId
                                );

                                if (
                                    mysqli_stmt_fetch(
                                        $mitraStatement
                                    )
                                ) {
                                    $databaseMitraId =
                                        $foundMitraId;
                                }

                                mysqli_stmt_close(
                                    $mitraStatement
                                );
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CARI MITRA PETUGAS
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $normalizedRole === 'petugas'
                        ) {
                            $staffStatement =
                                mysqli_prepare(
                                    $conn,
                                    "
                                        SELECT mitra_id
                                        FROM staff
                                        WHERE user_id = ?
                                        LIMIT 1
                                    "
                                );

                            if ($staffStatement) {
                                mysqli_stmt_bind_param(
                                    $staffStatement,
                                    'i',
                                    $databaseId
                                );

                                mysqli_stmt_execute(
                                    $staffStatement
                                );

                                mysqli_stmt_bind_result(
                                    $staffStatement,
                                    $staffMitraId
                                );

                                if (
                                    mysqli_stmt_fetch(
                                        $staffStatement
                                    )
                                ) {
                                    $databaseMitraId =
                                        $staffMitraId;
                                }

                                mysqli_stmt_close(
                                    $staffStatement
                                );
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | SIMPAN SESSION
                        |--------------------------------------------------------------------------
                        */

                        session_regenerate_id(true);

                        $authUser = [
                            'id' => (int) $databaseId,
                            'name' => (string) $databaseName,
                            'email' => (string) $databaseEmail,
                            'role' => $normalizedRole,
                            'mitra_id' =>
                                !empty($databaseMitraId)
                                    ? (int) $databaseMitraId
                                    : null
                        ];

                        $_SESSION['auth_user'] =
                            $authUser;

                        $_SESSION['user'] =
                            $authUser;

                        $_SESSION['user_id'] =
                            $authUser['id'];

                        $_SESSION['id'] =
                            $authUser['id'];

                        $_SESSION['uid'] =
                            $authUser['id'];

                        $_SESSION['login_id'] =
                            $authUser['id'];

                        $_SESSION['name'] =
                            $authUser['name'];

                        $_SESSION['user_name'] =
                            $authUser['name'];

                        $_SESSION['fullname'] =
                            $authUser['name'];

                        $_SESSION['login_name'] =
                            $authUser['name'];

                        $_SESSION['email'] =
                            $authUser['email'];

                        $_SESSION['user_email'] =
                            $authUser['email'];

                        $_SESSION['role'] =
                            $authUser['role'];

                        $_SESSION['user_role'] =
                            $authUser['role'];

                        $_SESSION['login_role'] =
                            $authUser['role'];

                        $_SESSION['mitra_id'] =
                            $authUser['mitra_id'];

                        $_SESSION['logged_in'] = true;
                        $_SESSION['is_logged_in'] = true;
                        $_SESSION['authenticated'] = true;

                        unset(
                            $_SESSION['login_csrf']
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | REDIRECT KELUHAN PELANGGAN
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $redirectTarget === 'complaints'
                            && $normalizedRole === 'buyer'
                        ) {
                            $buyerComplaintUrl =
                                loginFirstExistingPage(
                                    $baseUrl,
                                    $projectRoot,
                                    [
                                        'src/views/buyer/complaints.php',
                                        'src/views/buyer/create-complaint.php'
                                    ],
                                    'src/views/buyer/orders.php'
                                );

                            loginRedirect(
                                $buyerComplaintUrl
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | REDIRECT DASHBOARD
                        |--------------------------------------------------------------------------
                        */

                        loginRedirect(
                            loginDashboardUrl(
                                $normalizedRole,
                                $baseUrl,
                                $projectRoot
                            )
                        );
                    }
                }

                mysqli_stmt_close(
                    $statement
                );
            }
        }
    }
}

$redirectValue = trim(
    (string) (
        $_GET['redirect']
        ?? $_POST['redirect']
        ?? ''
    )
);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    <title>Login | Laundry UMKM</title>

    <style>
        :root {
            --primary: #0284c7;
            --secondary: #0ea5e9;
            --primary-dark: #075985;
            --dark: #07152d;
            --text: #334155;
            --muted: #64748b;
            --border: #b6e4fa;
            --soft: #f4fbff;
            --white: #ffffff;
            --danger: #dc2626;
            --success: #059669;
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

        body {
            width: 100%;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            background:
                linear-gradient(
                    135deg,
                    #e8f8ff 0%,
                    #f9fdff 50%,
                    #c9f9ff 100%
                );
            color: var(--dark);
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button,
        input {
            font: inherit;
        }

        a {
            color: inherit;
        }

        /*
        |--------------------------------------------------------------------------
        | NAVBAR
        |--------------------------------------------------------------------------
        */

        .login-navbar {
            position: relative;
            z-index: 100;
            width: 100%;
            min-height: 80px;
            border-bottom: 1px solid #d7effa;
            background:
                rgba(255, 255, 255, 0.98);
            box-shadow:
                0 5px 20px
                rgba(2, 132, 199, 0.05);
        }

        .login-navbar-container {
            display: flex;
            width: min(
                calc(100% - 32px),
                1135px
            );
            min-height: 80px;
            margin: 0 auto;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .login-brand {
            display: inline-flex;
            min-width: 0;
            align-items: center;
            gap: 11px;
            color: var(--primary-dark);
            text-decoration: none;
        }

        .login-brand-icon {
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
                    var(--secondary),
                    #2563eb
                );
            box-shadow:
                0 10px 23px
                rgba(2, 132, 199, 0.22);
            color: var(--white);
        }

        .login-brand-icon svg {
            width: 24px;
            height: 24px;
        }

        .login-brand-name {
            color: var(--primary-dark);
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.4px;
            white-space: nowrap;
        }

        .login-navbar-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .login-navbar-link {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 14px;
            border: 1px solid transparent;
            border-radius: 999px;
            color: var(--dark);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .login-navbar-link:hover,
        .login-navbar-link.active {
            border-color: var(--border);
            background: #e8f8ff;
            color: var(--primary-dark);
        }

        .login-navbar-link.register {
            padding-right: 20px;
            padding-left: 20px;
            background:
                linear-gradient(
                    135deg,
                    var(--secondary),
                    #2563eb
                );
            box-shadow:
                0 9px 22px
                rgba(2, 132, 199, 0.18);
            color: var(--white);
        }

        .login-navbar-link.register:hover {
            border-color: transparent;
            color: var(--white);
        }

        .login-navbar-link svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-menu-toggle {
            display: none;
            width: 42px;
            height: 42px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--primary-dark);
        }

        .login-menu-toggle svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN LOGIN
        |--------------------------------------------------------------------------
        */

        .login-page {
            display: flex;
            min-height:
                calc(100vh - 80px);
            align-items: center;
            padding: 45px 20px 65px;
        }

        .login-main-container {
            display: grid;
            width: min(
                935px,
                100%
            );
            margin: 0 auto;
            grid-template-columns:
                minmax(0, 1fr)
                minmax(380px, 478px);
            align-items: center;
            gap: 70px;
        }

        .login-introduction {
            min-width: 0;
            padding-left: 7px;
        }

        .login-eyebrow {
            margin: 0 0 12px;
            color: var(--primary);
            font-size: 14px;
            font-weight: 900;
        }

        .login-introduction h1 {
            max-width: 390px;
            margin: 0;
            color: var(--dark);
            font-size: clamp(
                40px,
                5vw,
                54px
            );
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -1.8px;
        }

        .login-introduction p {
            max-width: 430px;
            margin: 20px 0 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM CARD
        |--------------------------------------------------------------------------
        */

        .login-card {
            width: 100%;
            padding: 35px 31px 31px;
            border: 1px solid var(--border);
            border-radius: 25px;
            background:
                rgba(255, 255, 255, 0.98);
            box-shadow:
                0 24px 65px
                rgba(2, 132, 199, 0.12);
        }

        .login-card-header {
            margin-bottom: 24px;
        }

        .login-card-header h2 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 31px;
            font-weight: 900;
            line-height: 1.2;
        }

        .login-card-header p {
            margin: 11px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .login-alert {
            margin-bottom: 17px;
            padding: 13px 14px;
            border-radius: 13px;
            font-size: 13px;
            line-height: 1.5;
        }

        .login-alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .login-alert-success {
            border: 1px solid #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .login-field {
            margin-bottom: 17px;
        }

        .login-field label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
        }

        .login-input-wrapper {
            position: relative;
        }

        .login-input {
            width: 100%;
            height: 50px;
            padding: 0 15px;
            border: 1px solid var(--border);
            border-radius: 13px;
            outline: none;
            background: var(--soft);
            color: var(--dark);
            font-size: 15px;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .login-input-password {
            padding-right: 52px;
        }

        .login-input:focus {
            border-color: var(--secondary);
            box-shadow:
                0 0 0 4px
                rgba(14, 165, 233, 0.11);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            display: inline-flex;
            width: 37px;
            height: 37px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            transform: translateY(-50%);
        }

        .password-toggle:hover {
            background: #e0f2fe;
            color: var(--primary-dark);
        }

        .password-toggle svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-submit {
            display: flex;
            width: 100%;
            min-height: 48px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            margin-top: 4px;
            padding: 11px 18px;
            border: 0;
            border-radius: 999px;
            background:
                linear-gradient(
                    135deg,
                    var(--secondary),
                    #2563eb
                );
            box-shadow:
                0 11px 25px
                rgba(2, 132, 199, 0.2);
            color: var(--white);
            font-size: 14px;
            font-weight: 900;
        }

        .login-submit:hover {
            filter: brightness(0.97);
        }

        .login-register-text {
            margin: 20px 0 0;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        .login-register-text a {
            color: var(--primary);
            font-weight: 900;
            text-decoration: none;
        }

        .login-register-text a:hover {
            text-decoration: underline;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 900px) {
            .login-main-container {
                max-width: 620px;
                grid-template-columns:
                    minmax(0, 1fr);
                gap: 32px;
            }

            .login-introduction {
                padding-left: 0;
                text-align: center;
            }

            .login-introduction h1,
            .login-introduction p {
                margin-right: auto;
                margin-left: auto;
            }

            .login-card {
                max-width: 500px;
                margin: 0 auto;
            }
        }

        @media screen and (max-width: 760px) {
            .login-navbar {
                min-height: 68px;
            }

            .login-navbar-container {
                position: relative;
                width:
                    calc(100% - 24px);
                min-height: 68px;
            }

            .login-brand-icon {
                width: 40px;
                height: 40px;
                flex-basis: 40px;
            }

            .login-brand-name {
                font-size: 16px;
            }

            .login-menu-toggle {
                display: inline-flex;
            }

            .login-navbar-menu {
                position: absolute;
                top: calc(100% + 1px);
                right: 0;
                left: 0;
                display: none;
                padding: 12px;
                align-items: stretch;
                flex-direction: column;
                border: 1px solid var(--border);
                border-radius: 0 0 17px 17px;
                background: var(--white);
                box-shadow:
                    0 18px 35px
                    rgba(15, 23, 42, 0.15);
            }

            .login-navbar-menu.open {
                display: flex;
            }

            .login-navbar-link {
                width: 100%;
                min-height: 45px;
                justify-content: flex-start;
                padding: 11px 14px;
                border-color: #e0f2fe;
                border-radius: 12px;
                background: var(--soft);
            }

            .login-navbar-link.register {
                justify-content: center;
            }

            .login-page {
                min-height:
                    calc(100vh - 68px);
                padding:
                    38px
                    15px
                    45px;
            }

            .login-introduction h1 {
                font-size: 36px;
                letter-spacing: -1px;
            }

            .login-introduction p {
                font-size: 14px;
            }

            .login-card {
                padding: 28px 22px;
                border-radius: 21px;
            }

            .login-card-header h2 {
                font-size: 27px;
            }
        }

        @media screen and (max-width: 400px) {
            .login-brand-name {
                font-size: 15px;
            }

            .login-introduction h1 {
                font-size: 32px;
            }

            .login-card {
                padding:
                    25px
                    18px;
            }
        }
    </style>
</head>

<body>

<nav class="login-navbar">
    <div class="login-navbar-container">

        <a
            href="<?= loginEscape(
                $homeUrl
            ); ?>"
            class="login-brand"
        >
            <span class="login-brand-icon">
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

            <span class="login-brand-name">
                Laundry UMKM
            </span>
        </a>

        <button
            type="button"
            class="login-menu-toggle"
            id="loginMenuToggle"
            aria-label="Buka menu"
            aria-expanded="false"
        >
            <svg
                id="loginMenuIcon"
                viewBox="0 0 24 24"
            >
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
            </svg>
        </button>

        <div
            class="login-navbar-menu"
            id="loginNavbarMenu"
        >
            <a
                href="<?= loginEscape(
                    $homeUrl
                ); ?>"
                class="login-navbar-link"
            >
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

                Home
            </a>

            <a
                href="<?= loginEscape(
                    $loginPageUrl
                ); ?>"
                class="
                    login-navbar-link
                    active
                "
            >
                Login
            </a>

            <a
                href="<?= loginEscape(
                    $registerUrl
                ); ?>"
                class="
                    login-navbar-link
                    register
                "
            >
                Daftar Pelanggan
            </a>

            <a
                href="<?= loginEscape(
                    $complaintLoginUrl
                ); ?>"
                class="login-navbar-link"
            >
                Keluhan
            </a>
        </div>

    </div>
</nav>

<main class="login-page">
    <div class="login-main-container">

        <section class="login-introduction">
            <p class="login-eyebrow">
                Laundry UMKM
            </p>

            <h1>
                Masuk ke Sistem Laundry
            </h1>

            <p>
                Login sebagai admin, seller, petugas,
                customer service, atau pelanggan.
            </p>
        </section>

        <section class="login-card">

            <header class="login-card-header">
                <h2>Login</h2>

                <p>
                    Masukkan email dan password.
                </p>
            </header>

            <?php if (
                $successMessage !== ''
            ): ?>

                <div
                    class="
                        login-alert
                        login-alert-success
                    "
                >
                    <?= loginEscape(
                        $successMessage
                    ); ?>
                </div>

            <?php endif; ?>

            <?php if (
                $errorMessage !== ''
            ): ?>

                <div
                    class="
                        login-alert
                        login-alert-error
                    "
                >
                    <?= loginEscape(
                        $errorMessage
                    ); ?>
                </div>

            <?php endif; ?>

            <form
                method="post"
                action=""
                autocomplete="on"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= loginEscape(
                        $_SESSION[
                            'login_csrf'
                        ]
                    ); ?>"
                >

                <input
                    type="hidden"
                    name="redirect"
                    value="<?= loginEscape(
                        $redirectValue
                    ); ?>"
                >

                <div class="login-field">
                    <label for="email">
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="login-input"
                        value="<?= loginEscape(
                            $_POST['email']
                            ?? ''
                        ); ?>"
                        placeholder="Masukkan email"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="login-field">
                    <label for="password">
                        Password
                    </label>

                    <div class="login-input-wrapper">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="
                                login-input
                                login-input-password
                            "
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Tampilkan password"
                        >
                            <svg
                                id="passwordIcon"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"
                                ></path>

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                ></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="login-submit"
                >
                    Masuk
                </button>
            </form>

            <p class="login-register-text">
                Belum punya akun?

                <a
                    href="<?= loginEscape(
                        $registerUrl
                    ); ?>"
                >
                    Daftar pelanggan
                </a>
            </p>

        </section>

    </div>
</main>

<script>
(function () {
    /*
    |--------------------------------------------------------------------------
    | NAVBAR MOBILE
    |--------------------------------------------------------------------------
    */

    const menuToggle =
        document.getElementById(
            'loginMenuToggle'
        );

    const navbarMenu =
        document.getElementById(
            'loginNavbarMenu'
        );

    const menuIcon =
        document.getElementById(
            'loginMenuIcon'
        );

    const hamburgerIcon = `
        <path d="M4 7h16"></path>
        <path d="M4 12h16"></path>
        <path d="M4 17h16"></path>
    `;

    const closeIcon = `
        <path d="M6 6l12 12"></path>
        <path d="M18 6 6 18"></path>
    `;

    function closeMobileMenu() {
        if (
            !menuToggle
            || !navbarMenu
        ) {
            return;
        }

        navbarMenu.classList.remove(
            'open'
        );

        menuToggle.setAttribute(
            'aria-expanded',
            'false'
        );

        if (menuIcon) {
            menuIcon.innerHTML =
                hamburgerIcon;
        }
    }

    if (
        menuToggle
        && navbarMenu
    ) {
        menuToggle.addEventListener(
            'click',
            function () {
                const opened =
                    navbarMenu.classList.toggle(
                        'open'
                    );

                menuToggle.setAttribute(
                    'aria-expanded',
                    opened
                        ? 'true'
                        : 'false'
                );

                if (menuIcon) {
                    menuIcon.innerHTML =
                        opened
                            ? closeIcon
                            : hamburgerIcon;
                }
            }
        );

        navbarMenu
            .querySelectorAll('a')
            .forEach(function (link) {
                link.addEventListener(
                    'click',
                    closeMobileMenu
                );
            });

        document.addEventListener(
            'click',
            function (event) {
                if (
                    !navbarMenu.contains(
                        event.target
                    )
                    && !menuToggle.contains(
                        event.target
                    )
                ) {
                    closeMobileMenu();
                }
            }
        );

        window.addEventListener(
            'resize',
            function () {
                if (
                    window.innerWidth > 760
                ) {
                    closeMobileMenu();
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD
    |--------------------------------------------------------------------------
    */

    const passwordInput =
        document.getElementById(
            'password'
        );

    const passwordToggle =
        document.getElementById(
            'passwordToggle'
        );

    const passwordIcon =
        document.getElementById(
            'passwordIcon'
        );

    const showPasswordIcon = `
        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
        <circle cx="12" cy="12" r="3"></circle>
    `;

    const hidePasswordIcon = `
        <path d="M3 3l18 18"></path>
        <path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a17.2 17.2 0 0 1-3.1 3.8"></path>
        <path d="M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6a10.8 10.8 0 0 0 3.8-.7"></path>
        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
    `;

    if (
        passwordInput
        && passwordToggle
        && passwordIcon
    ) {
        passwordToggle.addEventListener(
            'click',
            function () {
                const isVisible =
                    passwordInput.type
                    === 'text';

                passwordInput.type =
                    isVisible
                        ? 'password'
                        : 'text';

                passwordIcon.innerHTML =
                    isVisible
                        ? showPasswordIcon
                        : hidePasswordIcon;

                passwordToggle.setAttribute(
                    'aria-label',
                    isVisible
                        ? 'Tampilkan password'
                        : 'Sembunyikan password'
                );
            }
        );
    }
})();
</script>

</body>
</html>