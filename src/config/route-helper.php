<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('appBaseUrl')) {
    function appBaseUrl(): string
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

            $srcPosition = strpos(
                $requestPath,
                '/src/'
            );

            if ($srcPosition !== false) {
                return rtrim(
                    substr(
                        $requestPath,
                        0,
                        $srcPosition
                    ),
                    '/'
                );
            }
        }

        return '';
    }
}

if (!function_exists('appUrl')) {
    function appUrl(string $path = ''): string
    {
        $baseUrl = appBaseUrl();

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl
            . '/'
            . ltrim($path, '/');
    }
}

if (!function_exists('appProjectRoot')) {
    function appProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}

if (!function_exists('appFileExists')) {
    function appFileExists(
        string $relativePath
    ): bool {
        $relativePath = ltrim(
            str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $relativePath
            ),
            DIRECTORY_SEPARATOR
        );

        return is_file(
            appProjectRoot()
            . DIRECTORY_SEPARATOR
            . $relativePath
        );
    }
}

if (!function_exists('appFirstUrl')) {
    function appFirstUrl(
        array $paths,
        string $fallback =
            'src/views/public/home.php'
    ): string {
        foreach ($paths as $path) {
            if (appFileExists($path)) {
                return appUrl($path);
            }
        }

        return appUrl($fallback);
    }
}

if (!function_exists('appNormalizeRole')) {
    function appNormalizeRole(
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
            'cs' => 'customer_service',
            'customer-service' =>
                'customer_service',
            'customer service' =>
                'customer_service',
            'customerservice' =>
                'customer_service'
        ];

        return $aliases[$role]
            ?? $role;
    }
}

if (!function_exists('appCurrentUser')) {
    function appCurrentUser(): ?array
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
            empty($userId)
            || (int) $userId < 1
        ) {
            return null;
        }

        return [
            'id' => (int) $userId,

            'name' => (string) (
                $_SESSION['name']
                ?? $_SESSION['user_name']
                ?? $_SESSION['fullname']
                ?? $_SESSION['login_name']
                ?? $sessionUser['name']
                ?? 'Pengguna Laundry'
            ),

            'email' => (string) (
                $_SESSION['email']
                ?? $_SESSION['user_email']
                ?? $sessionUser['email']
                ?? ''
            ),

            'role' => appNormalizeRole(
                $_SESSION['role']
                ?? $_SESSION['user_role']
                ?? $_SESSION['login_role']
                ?? $sessionUser['role']
                ?? ''
            ),

            'mitra_id' => !empty(
                $_SESSION['mitra_id']
                ?? $sessionUser['mitra_id']
                ?? null
            )
                ? (int) (
                    $_SESSION['mitra_id']
                    ?? $sessionUser['mitra_id']
                )
                : null
        ];
    }
}

if (!function_exists('appRedirect')) {
    function appRedirect(
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

if (!function_exists('appDashboardUrl')) {
    function appDashboardUrl(
        ?string $role = null
    ): string {
        if ($role === null) {
            $user = appCurrentUser();
            $role = $user['role'] ?? '';
        }

        $role = appNormalizeRole($role);

        switch ($role) {
            case 'admin':
                return appFirstUrl([
                    'src/views/admin/dashboard.php',
                    'src/views/admin/index.php'
                ]);

            case 'seller':
                return appFirstUrl([
                    'src/views/seller/dashboard.php',
                    'src/views/seller/index.php'
                ]);

            case 'petugas':
                return appFirstUrl([
                    'src/views/seller/petugas-dashboard.php',
                    'src/views/seller/petugas_dashboard.php',
                    'src/views/petugas/dashboard.php'
                ]);

            case 'buyer':
                return appFirstUrl([
                    'src/views/public/home.php',
                    'src/views/buyer/dashboard.php',
                    'src/views/buyer/orders.php'
                ]);

            case 'customer_service':
                return appFirstUrl([
                    'src/views/customer_service/dashboard.php',
                    'src/views/customer_service/complaints.php'
                ]);

            default:
                return appUrl(
                    'src/views/public/home.php'
                );
        }
    }
}

if (!function_exists('appRequireLogin')) {
    function appRequireLogin(): array
    {
        $user = appCurrentUser();

        if ($user === null) {
            appRedirect(
                appUrl(
                    'src/views/public/login.php'
                )
            );
        }

        return $user;
    }
}

if (!function_exists('appRequireRole')) {
    function appRequireRole(
        array $allowedRoles
    ): array {
        $user = appRequireLogin();

        $allowedRoles = array_map(
            'appNormalizeRole',
            $allowedRoles
        );

        if (
            !in_array(
                $user['role'],
                $allowedRoles,
                true
            )
        ) {
            appRedirect(
                appDashboardUrl(
                    $user['role']
                )
            );
        }

        return $user;
    }
}