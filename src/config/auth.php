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
| PROJECT ROOT
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_project_root')) {
    function laundry_project_root(): string
    {
        return dirname(__DIR__, 2);
    }
}

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_base_url')) {
    function laundry_base_url(): string
    {
        $requestPaths = [
            $_SERVER['SCRIPT_NAME'] ?? '',
            parse_url(
                $_SERVER['REQUEST_URI'] ?? '',
                PHP_URL_PATH
            ) ?: ''
        ];

        foreach ($requestPaths as $requestPath) {
            $requestPath = rawurldecode(
                str_replace('\\', '/', $requestPath)
            );

            $position = strpos(
                $requestPath,
                '/src/'
            );

            if ($position !== false) {
                return rtrim(
                    substr(
                        $requestPath,
                        0,
                        $position
                    ),
                    '/'
                );
            }
        }

        return '';
    }
}

if (!function_exists('laundry_url')) {
    function laundry_url(
        string $path = ''
    ): string {
        $baseUrl = laundry_base_url();

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl
            . '/'
            . ltrim($path, '/');
    }
}

/*
|--------------------------------------------------------------------------
| NORMALISASI ROLE
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_normalize_role')) {
    function laundry_normalize_role(
        ?string $role
    ): string {
        $role = strtolower(
            trim((string) $role)
        );

        $roleAliases = [
            'mitra' => 'seller',
            'penjual' => 'seller',
            'pelanggan' => 'buyer',
            'customer' => 'buyer',
            'kurir' => 'petugas',
            'customer-service' =>
                'customer_service',
            'customer service' =>
                'customer_service',
            'customerservice' =>
                'customer_service',
            'cs' => 'customer_service'
        ];

        return $roleAliases[$role]
            ?? $role;
    }
}

/*
|--------------------------------------------------------------------------
| DATA USER DARI SESSION
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_auth_user')) {
    function laundry_auth_user(): ?array
    {
        $sessionUser = [];

        if (
            isset($_SESSION['auth_user'])
            && is_array(
                $_SESSION['auth_user']
            )
        ) {
            $sessionUser =
                $_SESSION['auth_user'];
        } elseif (
            isset($_SESSION['user'])
            && is_array(
                $_SESSION['user']
            )
        ) {
            $sessionUser =
                $_SESSION['user'];
        }

        $userId =
            $_SESSION['user_id']
            ?? $_SESSION['id']
            ?? $_SESSION['uid']
            ?? $_SESSION['login_id']
            ?? $sessionUser['id']
            ?? null;

        if (
            $userId === null
            || (int) $userId < 1
        ) {
            return null;
        }

        $userRole =
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? $_SESSION['login_role']
            ?? $sessionUser['role']
            ?? '';

        $userName =
            $_SESSION['name']
            ?? $_SESSION['user_name']
            ?? $_SESSION['fullname']
            ?? $_SESSION['login_name']
            ?? $sessionUser['name']
            ?? 'Pengguna Laundry';

        $userEmail =
            $_SESSION['email']
            ?? $_SESSION['user_email']
            ?? $sessionUser['email']
            ?? '';

        $mitraId =
            $_SESSION['mitra_id']
            ?? $sessionUser['mitra_id']
            ?? null;

        return [
            'id' => (int) $userId,
            'name' => (string) $userName,
            'email' => (string) $userEmail,
            'role' =>
                laundry_normalize_role(
                    $userRole
                ),
            'mitra_id' =>
                $mitraId !== null
                && $mitraId !== ''
                    ? (int) $mitraId
                    : null
        ];
    }
}

/*
|--------------------------------------------------------------------------
| SIMPAN SESSION LOGIN
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_set_auth_user')) {
    function laundry_set_auth_user(
        array $user
    ): void {
        $normalizedUser = [
            'id' => (int) (
                $user['id']
                ?? 0
            ),

            'name' => (string) (
                $user['name']
                ?? 'Pengguna Laundry'
            ),

            'email' => (string) (
                $user['email']
                ?? ''
            ),

            'role' =>
                laundry_normalize_role(
                    $user['role']
                    ?? ''
                ),

            'mitra_id' =>
                isset($user['mitra_id'])
                && $user['mitra_id'] !== ''
                    ? (int) $user['mitra_id']
                    : null
        ];

        session_regenerate_id(true);

        $_SESSION['auth_user'] =
            $normalizedUser;

        $_SESSION['user'] =
            $normalizedUser;

        $_SESSION['user_id'] =
            $normalizedUser['id'];

        $_SESSION['id'] =
            $normalizedUser['id'];

        $_SESSION['uid'] =
            $normalizedUser['id'];

        $_SESSION['login_id'] =
            $normalizedUser['id'];

        $_SESSION['name'] =
            $normalizedUser['name'];

        $_SESSION['user_name'] =
            $normalizedUser['name'];

        $_SESSION['fullname'] =
            $normalizedUser['name'];

        $_SESSION['login_name'] =
            $normalizedUser['name'];

        $_SESSION['email'] =
            $normalizedUser['email'];

        $_SESSION['user_email'] =
            $normalizedUser['email'];

        $_SESSION['role'] =
            $normalizedUser['role'];

        $_SESSION['user_role'] =
            $normalizedUser['role'];

        $_SESSION['login_role'] =
            $normalizedUser['role'];

        $_SESSION['mitra_id'] =
            $normalizedUser['mitra_id'];

        $_SESSION['logged_in'] = true;
        $_SESSION['is_logged_in'] = true;
        $_SESSION['authenticated'] = true;
    }
}

/*
|--------------------------------------------------------------------------
| CEK FILE HALAMAN
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_page_exists')) {
    function laundry_page_exists(
        string $relativePath
    ): bool {
        $relativePath = ltrim(
            str_replace(
                '\\',
                '/',
                $relativePath
            ),
            '/'
        );

        $fullPath =
            laundry_project_root()
            . DIRECTORY_SEPARATOR
            . str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        return is_file($fullPath);
    }
}

/*
|--------------------------------------------------------------------------
| CARI HALAMAN YANG BENAR-BENAR ADA
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_first_existing_page')) {
    function laundry_first_existing_page(
        array $candidates,
        string $fallback =
            'src/views/public/home.php'
    ): string {
        foreach (
            $candidates
            as $candidate
        ) {
            if (
                laundry_page_exists(
                    $candidate
                )
            ) {
                return laundry_url(
                    $candidate
                );
            }
        }

        return laundry_url($fallback);
    }
}

/*
|--------------------------------------------------------------------------
| DASHBOARD BERDASARKAN ROLE
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_dashboard_url')) {
    function laundry_dashboard_url(
        ?string $role = null
    ): string {
        if ($role === null) {
            $currentUser =
                laundry_auth_user();

            $role =
                $currentUser['role']
                ?? '';
        }

        $role =
            laundry_normalize_role(
                $role
            );

        switch ($role) {
            case 'admin':
                return laundry_first_existing_page([
                    'src/views/admin/dashboard.php',
                    'src/views/admin/index.php'
                ]);

            case 'seller':
                return laundry_first_existing_page([
                    'src/views/seller/dashboard.php',
                    'src/views/mitra/dashboard.php',
                    'src/views/seller/index.php'
                ]);

            case 'petugas':
                return laundry_first_existing_page([
                    'src/views/seller/petugas-dashboard.php',
                    'src/views/seller/petugas_dashboard.php',
                    'src/views/petugas/dashboard.php',
                    'src/views/staff/dashboard.php',
                    'src/views/seller/dashboard.php'
                ]);

            case 'buyer':
                return laundry_first_existing_page([
                    'src/views/public/home.php',
                    'src/views/buyer/dashboard.php',
                    'src/views/buyer/orders.php'
                ]);

            case 'customer_service':
                return laundry_first_existing_page([
                    'src/views/customer_service/dashboard.php',
                    'src/views/customer-service/dashboard.php',
                    'src/views/customerservice/dashboard.php',
                    'src/views/cs/dashboard.php',
                    'src/views/public/home.php'
                ]);

            default:
                return laundry_url(
                    'src/views/public/home.php'
                );
        }
    }
}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_redirect')) {
    function laundry_redirect(
        string $url
    ): void {
        if (!headers_sent()) {
            header(
                'Location: ' . $url
            );

            exit;
        }

        echo '<script>';
        echo 'window.location.href='
            . json_encode($url)
            . ';';
        echo '</script>';

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GUARD LOGIN
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_require_login')) {
    function laundry_require_login(): array
    {
        $currentUser =
            laundry_auth_user();

        if ($currentUser === null) {
            $_SESSION['intended_url'] =
                $_SERVER['REQUEST_URI']
                ?? '';

            laundry_redirect(
                laundry_url(
                    'src/views/public/login.php'
                )
            );
        }

        return $currentUser;
    }
}

/*
|--------------------------------------------------------------------------
| GUARD ROLE
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_require_roles')) {
    function laundry_require_roles(
        array $allowedRoles
    ): array {
        $currentUser =
            laundry_require_login();

        $allowedRoles = array_map(
            'laundry_normalize_role',
            $allowedRoles
        );

        if (
            !in_array(
                $currentUser['role'],
                $allowedRoles,
                true
            )
        ) {
            laundry_redirect(
                laundry_dashboard_url(
                    $currentUser['role']
                )
            );
        }

        return $currentUser;
    }
}

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

if (!function_exists('laundry_destroy_session')) {
    function laundry_destroy_session(): void
    {
        $_SESSION = [];

        if (
            ini_get(
                'session.use_cookies'
            )
        ) {
            $cookieParameters =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $cookieParameters['path'],
                $cookieParameters['domain'],
                $cookieParameters['secure'],
                $cookieParameters['httponly']
            );
        }

        if (
            session_status() ===
            PHP_SESSION_ACTIVE
        ) {
            session_destroy();
        }
    }
}