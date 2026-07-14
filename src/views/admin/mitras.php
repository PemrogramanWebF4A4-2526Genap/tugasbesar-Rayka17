<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/route-helper.php';

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl = appBaseUrl();

$urls = [
    'login' => $baseUrl . '/src/views/public/login.php',
    'logout' => $baseUrl . '/src/views/public/logout.php',
    'home' => $baseUrl . '/src/views/public/home.php',
    'dashboard' => $baseUrl . '/src/views/admin/dashboard.php',
    'users' => $baseUrl . '/src/views/admin/users.php',
    'mitras' => $baseUrl . '/src/views/admin/mitras.php',
    'staff' => $baseUrl . '/src/views/admin/staff.php',
    'services' => $baseUrl . '/src/views/admin/services.php',
    'orders' => $baseUrl . '/src/views/admin/orders.php',
    'complaints' => $baseUrl . '/src/views/admin/complaints.php'
];

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

$sessionUser = [];

if (
    isset($_SESSION['auth_user'])
    && is_array($_SESSION['auth_user'])
) {
    $sessionUser = $_SESSION['auth_user'];
} elseif (
    isset($_SESSION['user'])
    && is_array($_SESSION['user'])
) {
    $sessionUser = $_SESSION['user'];
}

$currentUserId =
    $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? $_SESSION['uid']
    ?? $_SESSION['login_id']
    ?? $sessionUser['id']
    ?? null;

$currentUserName =
    $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['fullname']
    ?? $_SESSION['login_name']
    ?? $sessionUser['name']
    ?? 'Admin Laundry';

$currentUserRole = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? $_SESSION['login_role']
            ?? $sessionUser['role']
            ?? ''
        )
    )
);

if (empty($currentUserId)) {
    header('Location: ' . $urls['login']);
    exit;
}

if ($currentUserRole !== 'admin') {
    header('Location: ' . $urls['home']);
    exit;
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function mitraEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function mitraCurrency($value): string
{
    return 'Rp ' . number_format(
        (float) $value,
        0,
        ',',
        '.'
    );
}

function mitraDate($value): string
{
    if (
        empty($value)
        || $value === '0000-00-00 00:00:00'
    ) {
        return '-';
    }

    $timestamp = strtotime((string) $value);

    if (!$timestamp) {
        return '-';
    }

    return date('d/m/Y H:i', $timestamp);
}

function mitraRedirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['seller_admin_csrf'])) {
    $_SESSION['seller_admin_csrf'] = bin2hex(
        random_bytes(32)
    );
}

/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION['seller_admin_success']
    ?? '';

$errorMessage =
    $_SESSION['seller_admin_error']
    ?? '';

unset(
    $_SESSION['seller_admin_success'],
    $_SESSION['seller_admin_error']
);

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) (
        $_POST['csrf_token']
        ?? ''
    );

    $sessionToken = (string) (
        $_SESSION['seller_admin_csrf']
        ?? ''
    );

    if (
        $csrfToken === ''
        || $sessionToken === ''
        || !hash_equals(
            $sessionToken,
            $csrfToken
        )
    ) {
        $_SESSION['seller_admin_error'] =
            'Sesi formulir tidak valid.';

        mitraRedirect($urls['mitras']);
    }

    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );

    /*
    |--------------------------------------------------------------------------
    | TAMBAH SELLER
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {
        $accountName = trim(
            (string) (
                $_POST['account_name']
                ?? ''
            )
        );

        $email = strtolower(
            trim(
                (string) (
                    $_POST['email']
                    ?? ''
                )
            )
        );

        $password = (string) (
            $_POST['password']
            ?? ''
        );

        $mitraName = trim(
            (string) (
                $_POST['mitra_name']
                ?? ''
            )
        );

        $ownerName = trim(
            (string) (
                $_POST['owner_name']
                ?? ''
            )
        );

        $phone = trim(
            (string) (
                $_POST['phone']
                ?? ''
            )
        );

        $city = trim(
            (string) (
                $_POST['city']
                ?? ''
            )
        );

        $address = trim(
            (string) (
                $_POST['address']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $_POST['description']
                ?? ''
            )
        );

        $pickupFee = max(
            0,
            (int) (
                $_POST['pickup_fee']
                ?? 0
            )
        );

        $deliveryFee = max(
            0,
            (int) (
                $_POST['delivery_fee']
                ?? 0
            )
        );

        $mitraStatus = strtolower(
            trim(
                (string) (
                    $_POST['status']
                    ?? 'active'
                )
            )
        );

        $allowedStatuses = [
            'pending',
            'active',
            'blocked'
        ];

        if (
            !in_array(
                $mitraStatus,
                $allowedStatuses,
                true
            )
        ) {
            $mitraStatus = 'active';
        }

        if (
            $accountName === ''
            || $email === ''
            || $password === ''
            || $mitraName === ''
            || $ownerName === ''
        ) {
            $_SESSION['seller_admin_error'] =
                'Nama akun, email, password, nama laundry, dan nama pemilik wajib diisi.';

            mitraRedirect($urls['mitras']);
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $_SESSION['seller_admin_error'] =
                'Format email tidak valid.';

            mitraRedirect($urls['mitras']);
        }

        if (strlen($password) < 6) {
            $_SESSION['seller_admin_error'] =
                'Password minimal enam karakter.';

            mitraRedirect($urls['mitras']);
        }

        $checkEmail = mysqli_prepare(
            $conn,
            "
                SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1
            "
        );

        if (!$checkEmail) {
            $_SESSION['seller_admin_error'] =
                'Sistem gagal memeriksa email.';

            mitraRedirect($urls['mitras']);
        }

        mysqli_stmt_bind_param(
            $checkEmail,
            's',
            $email
        );

        mysqli_stmt_execute($checkEmail);
        mysqli_stmt_store_result($checkEmail);

        $emailExists =
            mysqli_stmt_num_rows($checkEmail) > 0;

        mysqli_stmt_close($checkEmail);

        if ($emailExists) {
            $_SESSION['seller_admin_error'] =
                'Email tersebut sudah digunakan.';

            mitraRedirect($urls['mitras']);
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $userStatus =
            $mitraStatus === 'active'
                ? 'active'
                : 'blocked';

        mysqli_begin_transaction($conn);

        try {
            $userStatement = mysqli_prepare(
                $conn,
                "
                    INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role,
                        mitra_id,
                        status,
                        phone,
                        address,
                        created_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'seller',
                        NULL,
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                "
            );

            if (!$userStatement) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $userStatement,
                'ssssss',
                $accountName,
                $email,
                $passwordHash,
                $userStatus,
                $phone,
                $address
            );

            if (
                !mysqli_stmt_execute(
                    $userStatement
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $userStatement
                    )
                );
            }

            $newUserId = mysqli_insert_id($conn);

            mysqli_stmt_close($userStatement);

            $mitraStatement = mysqli_prepare(
                $conn,
                "
                    INSERT INTO laundry_mitras
                    (
                        user_id,
                        mitra_name,
                        owner_name,
                        phone,
                        city,
                        address,
                        description,
                        logo,
                        pickup_fee,
                        delivery_fee,
                        status,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NULL,
                        ?,
                        ?,
                        ?,
                        NOW(),
                        NOW()
                    )
                "
            );

            if (!$mitraStatement) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $mitraStatement,
                'issssssiis',
                $newUserId,
                $mitraName,
                $ownerName,
                $phone,
                $city,
                $address,
                $description,
                $pickupFee,
                $deliveryFee,
                $mitraStatus
            );

            if (
                !mysqli_stmt_execute(
                    $mitraStatement
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $mitraStatement
                    )
                );
            }

            $newMitraId = mysqli_insert_id($conn);

            mysqli_stmt_close($mitraStatement);

            $updateUser = mysqli_prepare(
                $conn,
                "
                    UPDATE users
                    SET mitra_id = ?
                    WHERE id = ?
                "
            );

            if (!$updateUser) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $updateUser,
                'ii',
                $newMitraId,
                $newUserId
            );

            if (
                !mysqli_stmt_execute(
                    $updateUser
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $updateUser
                    )
                );
            }

            mysqli_stmt_close($updateUser);

            mysqli_commit($conn);

            $_SESSION['seller_admin_success'] =
                'Seller baru berhasil ditambahkan.';
        } catch (Throwable $error) {
            mysqli_rollback($conn);

            $_SESSION['seller_admin_error'] =
                'Seller gagal ditambahkan: '
                . $error->getMessage();
        }

        mitraRedirect($urls['mitras']);
    }

    /*
    |--------------------------------------------------------------------------
    | UBAH STATUS
    |--------------------------------------------------------------------------
    */

    if ($action === 'status') {
        $mitraId = (int) (
            $_POST['mitra_id']
            ?? 0
        );

        $userId = (int) (
            $_POST['user_id']
            ?? 0
        );

        $newStatus = strtolower(
            trim(
                (string) (
                    $_POST['new_status']
                    ?? ''
                )
            )
        );

        $allowedStatuses = [
            'pending',
            'active',
            'blocked'
        ];

        if (
            $mitraId < 1
            || $userId < 1
            || !in_array(
                $newStatus,
                $allowedStatuses,
                true
            )
        ) {
            $_SESSION['seller_admin_error'] =
                'Data perubahan status tidak valid.';

            mitraRedirect($urls['mitras']);
        }

        $userStatus =
            $newStatus === 'active'
                ? 'active'
                : 'blocked';

        mysqli_begin_transaction($conn);

        try {
            $mitraStatusStatement = mysqli_prepare(
                $conn,
                "
                    UPDATE laundry_mitras
                    SET
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                    AND user_id = ?
                "
            );

            if (!$mitraStatusStatement) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $mitraStatusStatement,
                'sii',
                $newStatus,
                $mitraId,
                $userId
            );

            if (
                !mysqli_stmt_execute(
                    $mitraStatusStatement
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $mitraStatusStatement
                    )
                );
            }

            mysqli_stmt_close(
                $mitraStatusStatement
            );

            $userStatusStatement = mysqli_prepare(
                $conn,
                "
                    UPDATE users
                    SET status = ?
                    WHERE id = ?
                    AND role IN ('seller', 'mitra')
                "
            );

            if (!$userStatusStatement) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $userStatusStatement,
                'si',
                $userStatus,
                $userId
            );

            if (
                !mysqli_stmt_execute(
                    $userStatusStatement
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $userStatusStatement
                    )
                );
            }

            mysqli_stmt_close(
                $userStatusStatement
            );

            mysqli_commit($conn);

            $_SESSION['seller_admin_success'] =
                'Status seller berhasil diperbarui.';
        } catch (Throwable $error) {
            mysqli_rollback($conn);

            $_SESSION['seller_admin_error'] =
                'Status seller gagal diperbarui.';
        }

        mitraRedirect($urls['mitras']);
    }

    /*
    |--------------------------------------------------------------------------
    | HAPUS SELLER
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {
        $mitraId = (int) (
            $_POST['mitra_id']
            ?? 0
        );

        $userId = (int) (
            $_POST['user_id']
            ?? 0
        );

        if ($mitraId < 1 || $userId < 1) {
            $_SESSION['seller_admin_error'] =
                'Data seller tidak valid.';

            mitraRedirect($urls['mitras']);
        }

        $relationStatement = mysqli_prepare(
            $conn,
            "
                SELECT
                    (
                        SELECT COUNT(*)
                        FROM laundry_services
                        WHERE mitra_id = ?
                    ) AS total_services,

                    (
                        SELECT COUNT(*)
                        FROM laundry_orders
                        WHERE mitra_id = ?
                    ) AS total_orders,

                    (
                        SELECT COUNT(*)
                        FROM staff
                        WHERE mitra_id = ?
                    ) AS total_staff
            "
        );

        if (!$relationStatement) {
            $_SESSION['seller_admin_error'] =
                'Sistem gagal memeriksa relasi seller.';

            mitraRedirect($urls['mitras']);
        }

        mysqli_stmt_bind_param(
            $relationStatement,
            'iii',
            $mitraId,
            $mitraId,
            $mitraId
        );

        mysqli_stmt_execute($relationStatement);

        mysqli_stmt_bind_result(
            $relationStatement,
            $serviceTotal,
            $orderTotal,
            $staffTotal
        );

        mysqli_stmt_fetch($relationStatement);
        mysqli_stmt_close($relationStatement);

        $serviceTotal = (int) $serviceTotal;
        $orderTotal = (int) $orderTotal;
        $staffTotal = (int) $staffTotal;

        if (
            $serviceTotal > 0
            || $orderTotal > 0
            || $staffTotal > 0
        ) {
            $_SESSION['seller_admin_error'] =
                'Seller tidak dapat dihapus karena masih mempunyai '
                . $serviceTotal
                . ' layanan, '
                . $staffTotal
                . ' petugas, dan '
                . $orderTotal
                . ' pesanan. Gunakan fitur blokir seller.';

            mitraRedirect($urls['mitras']);
        }

        mysqli_begin_transaction($conn);

        try {
            $clearUser = mysqli_prepare(
                $conn,
                "
                    UPDATE users
                    SET mitra_id = NULL
                    WHERE id = ?
                    AND role IN ('seller', 'mitra')
                "
            );

            if (!$clearUser) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $clearUser,
                'i',
                $userId
            );

            mysqli_stmt_execute($clearUser);
            mysqli_stmt_close($clearUser);

            $deleteMitra = mysqli_prepare(
                $conn,
                "
                    DELETE FROM laundry_mitras
                    WHERE id = ?
                    AND user_id = ?
                "
            );

            if (!$deleteMitra) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $deleteMitra,
                'ii',
                $mitraId,
                $userId
            );

            if (
                !mysqli_stmt_execute(
                    $deleteMitra
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $deleteMitra
                    )
                );
            }

            mysqli_stmt_close($deleteMitra);

            $deleteUser = mysqli_prepare(
                $conn,
                "
                    DELETE FROM users
                    WHERE id = ?
                    AND role IN ('seller', 'mitra')
                "
            );

            if (!$deleteUser) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $deleteUser,
                'i',
                $userId
            );

            if (
                !mysqli_stmt_execute(
                    $deleteUser
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $deleteUser
                    )
                );
            }

            mysqli_stmt_close($deleteUser);

            mysqli_commit($conn);

            $_SESSION['seller_admin_success'] =
                'Seller berhasil dihapus.';
        } catch (Throwable $error) {
            mysqli_rollback($conn);

            $_SESSION['seller_admin_error'] =
                'Seller gagal dihapus karena masih terhubung dengan data lain.';
        }

        mitraRedirect($urls['mitras']);
    }

    $_SESSION['seller_admin_error'] =
        'Aksi tidak dikenali.';

    mitraRedirect($urls['mitras']);
}

/*
|--------------------------------------------------------------------------
| RINGKASAN
|--------------------------------------------------------------------------
*/

$summary = [
    'total' => 0,
    'active' => 0,
    'pending' => 0,
    'blocked' => 0
];

$summaryQuery = mysqli_query(
    $conn,
    "
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN status = 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS total_active,

            SUM(
                CASE
                    WHEN status = 'pending'
                    THEN 1
                    ELSE 0
                END
            ) AS total_pending,

            SUM(
                CASE
                    WHEN status = 'blocked'
                    THEN 1
                    ELSE 0
                END
            ) AS total_blocked

        FROM laundry_mitras
    "
);

if ($summaryQuery) {
    $summaryRow = mysqli_fetch_assoc(
        $summaryQuery
    );

    $summary['total'] = (int) (
        $summaryRow['total']
        ?? 0
    );

    $summary['active'] = (int) (
        $summaryRow['total_active']
        ?? 0
    );

    $summary['pending'] = (int) (
        $summaryRow['total_pending']
        ?? 0
    );

    $summary['blocked'] = (int) (
        $summaryRow['total_blocked']
        ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| DAFTAR SELLER
|--------------------------------------------------------------------------
*/

$mitras = [];

$mitraQuery = mysqli_query(
    $conn,
    "
        SELECT
            lm.id,
            lm.user_id,
            lm.mitra_name,
            lm.owner_name,
            lm.phone,
            lm.city,
            lm.address,
            lm.description,
            lm.pickup_fee,
            lm.delivery_fee,
            lm.status,
            lm.created_at,

            u.name AS account_name,
            u.email AS account_email,
            u.status AS account_status,

            (
                SELECT COUNT(*)
                FROM laundry_services AS ls
                WHERE ls.mitra_id = lm.id
            ) AS service_total,

            (
                SELECT COUNT(*)
                FROM staff AS st
                WHERE st.mitra_id = lm.id
            ) AS staff_total,

            (
                SELECT COUNT(*)
                FROM laundry_orders AS lo
                WHERE lo.mitra_id = lm.id
            ) AS order_total

        FROM laundry_mitras AS lm

        LEFT JOIN users AS u
            ON u.id = lm.user_id

        ORDER BY
            lm.created_at DESC,
            lm.id DESC
    "
);

if ($mitraQuery) {
    while (
        $mitra = mysqli_fetch_assoc(
            $mitraQuery
        )
    ) {
        $mitras[] = $mitra;
    }
}

$adminInitial =
    function_exists('mb_substr')
        ? mb_substr(
            trim($currentUserName),
            0,
            1
        )
        : substr(
            trim($currentUserName),
            0,
            1
        );

$adminInitial = strtoupper(
    $adminInitial !== ''
        ? $adminInitial
        : 'A'
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

    <title>Kelola Seller | Laundry UMKM</title>

    <style>
        :root {
            --sidebar-width: 245px;
            --topbar-height: 76px;
            --primary: #0284c7;
            --primary-light: #0ea5e9;
            --primary-dark: #075985;
            --sidebar: #063b54;
            --sidebar-dark: #032f44;
            --dark: #07152d;
            --text: #334155;
            --muted: #64748b;
            --border: #b6e4fa;
            --soft: #f4fbff;
            --white: #ffffff;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            width: 100%;
            min-height: 100%;
            scroll-behavior: smooth;
        }

        body {
            width: 100%;
            min-width: 0;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;

            background:
                linear-gradient(
                    135deg,
                    #e5f7ff 0%,
                    #f9fdff 52%,
                    #c8f7ff 100%
                );

            color: var(--dark);

            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;

            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        button,
        input,
        select,
        textarea {
            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            font-size: 14px;
            font-weight: 400;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .admin-sidebar {
            position: fixed;
            z-index: 1200;
            top: 0;
            bottom: 0;
            left: 0;

            width: var(--sidebar-width);
            height: 100vh;

            padding: 20px 14px 24px;

            overflow-x: hidden;
            overflow-y: auto;

            background:
                linear-gradient(
                    180deg,
                    var(--sidebar) 0%,
                    var(--sidebar-dark) 100%
                );

            color: var(--white);

            box-shadow:
                8px 0 30px
                rgba(15, 23, 42, 0.12);

            scrollbar-width: none;
        }

        .admin-sidebar::-webkit-scrollbar {
            display: none;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 7px 7px 20px;

            color: var(--white);
            text-decoration: none;
        }

        .admin-brand-logo {
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
                    var(--primary-light),
                    #2563eb
                );

            box-shadow:
                0 10px 22px
                rgba(14, 165, 233, 0.23);

            color: var(--white);

            font-size: 15px;
            font-weight: 700;
        }

        .admin-brand-text {
            min-width: 0;
        }

        .admin-brand-text strong,
        .admin-brand-text span {
            display: block;
        }

        .admin-brand-text strong {
            overflow: hidden;

            color: var(--white);

            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-brand-text span {
            margin-top: 4px;

            color: #bae6fd;

            font-size: 11px;
            font-weight: 400;
        }

        .admin-profile {
            margin-bottom: 16px;

            padding: 14px 13px;

            border:
                1px solid
                rgba(186, 230, 253, 0.2);

            border-radius: 15px;

            background:
                rgba(255, 255, 255, 0.08);
        }

        .admin-profile strong,
        .admin-profile span {
            display: block;
        }

        .admin-profile strong {
            overflow: hidden;

            color: var(--white);

            font-size: 14px;
            font-weight: 600;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-profile span {
            margin-top: 5px;

            color: #bae6fd;

            font-size: 11px;
            font-weight: 400;
        }

        .admin-menu {
            display: grid;
            gap: 7px;
        }

        .admin-menu-link {
            display: flex;

            width: 100%;
            min-height: 45px;

            align-items: center;

            gap: 11px;

            padding: 11px 12px;

            border: 1px solid transparent;
            border-radius: 12px;

            color: #dbeafe;

            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;

            text-decoration: none;

            transition:
                background 0.2s ease,
                color 0.2s ease,
                border-color 0.2s ease;
        }

        .admin-menu-link:hover,
        .admin-menu-link.active {
            border-color:
                rgba(186, 230, 253, 0.14);

            background:
                rgba(255, 255, 255, 0.14);

            color: var(--white);
        }

        .admin-menu-link svg {
            width: 18px;
            height: 18px;

            flex: 0 0 18px;

            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .admin-divider {
            height: 1px;

            margin: 10px 5px;

            background:
                rgba(186, 230, 253, 0.16);
        }

        .admin-home-link {
            background:
                rgba(14, 165, 233, 0.1);
        }

        .admin-logout-link {
            background: var(--white);
            color: var(--primary-dark);
        }

        .admin-logout-link:hover {
            border-color: transparent;
            background: #e0f2fe;
            color: var(--primary-dark);
        }

        /*
        |--------------------------------------------------------------------------
        | TOPBAR
        |--------------------------------------------------------------------------
        */

        .admin-topbar {
            position: fixed;
            z-index: 1100;

            top: 0;
            right: 0;
            left: var(--sidebar-width);

            display: flex;

            min-height: var(--topbar-height);

            padding: 0 28px;

            align-items: center;
            justify-content: space-between;

            gap: 18px;

            border-bottom:
                1px solid
                var(--border);

            background:
                rgba(255, 255, 255, 0.98);

            box-shadow:
                0 5px 20px
                rgba(2, 132, 199, 0.06);
        }

        .admin-topbar-title {
            min-width: 0;
        }

        .admin-topbar-title h1 {
            margin: 0;

            color: var(--primary-dark);

            font-size: 20px;
            font-weight: 700;
            line-height: 1.25;

            letter-spacing: -0.02em;
        }

        .admin-topbar-title p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 13px;
            font-weight: 400;
        }

        .admin-topbar-right {
            display: flex;

            flex: 0 0 auto;

            align-items: center;

            gap: 13px;
        }

        .admin-clock {
            display: inline-flex;

            min-height: 40px;

            align-items: center;

            padding: 9px 14px;

            border:
                1px solid
                var(--border);

            border-radius: 999px;

            background: #e7f7ff;

            color: var(--primary-dark);

            font-size: 12px;
            font-weight: 600;

            white-space: nowrap;
        }

        .admin-topbar-user {
            min-width: 0;
            text-align: right;
        }

        .admin-topbar-user strong,
        .admin-topbar-user span {
            display: block;
        }

        .admin-topbar-user strong {
            color: #ec4899;

            font-size: 13px;
            font-weight: 600;
        }

        .admin-topbar-user span {
            margin-top: 2px;

            color: #ec4899;

            font-size: 12px;
            font-weight: 400;
        }

        .admin-avatar {
            display: inline-flex;

            width: 43px;
            height: 43px;

            flex: 0 0 43px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    var(--primary-light),
                    var(--primary)
                );

            color: var(--white);

            font-size: 14px;
            font-weight: 700;
        }

        .admin-menu-toggle,
        .admin-overlay {
            display: none;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .admin-main {
            width:
                calc(
                    100% - var(--sidebar-width)
                );

            min-width: 0;
            min-height: 100vh;

            margin-left: var(--sidebar-width);

            padding:
                calc(
                    var(--topbar-height)
                    + 28px
                )
                26px
                55px;
        }

        .admin-container {
            width: min(1100px, 100%);
            margin: 0 auto;
        }

        .page-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;
        }

        .page-eyebrow {
            margin: 0 0 8px;

            color: var(--primary);

            font-size: 14px;
            font-weight: 600;
        }

        .page-title {
            margin: 0;

            color: var(--dark);

            font-size: 34px;
            font-weight: 700;
            line-height: 1.18;

            letter-spacing: -0.03em;
        }

        .page-description {
            margin: 10px 0 0;

            color: var(--muted);

            font-size: 14px;
            font-weight: 400;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .admin-button {
            display: inline-flex;

            min-height: 43px;

            cursor: pointer;

            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 10px 18px;

            border: 1px solid transparent;
            border-radius: 999px;

            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;

            text-decoration: none;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;
        }

        .admin-button:hover {
            transform: translateY(-1px);
        }

        .admin-button-primary {
            background:
                linear-gradient(
                    135deg,
                    var(--primary-light),
                    #2563eb
                );

            box-shadow:
                0 10px 23px
                rgba(2, 132, 199, 0.18);

            color: var(--white);
        }

        .admin-button-outline {
            border-color: var(--border);
            background: var(--white);
            color: var(--primary-dark);
        }

        .admin-button-success {
            background: var(--success);
            color: var(--white);
        }

        .admin-button-warning {
            background: var(--warning);
            color: var(--white);
        }

        .admin-button-danger {
            background: var(--danger);
            color: var(--white);
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .admin-alert {
            margin-top: 18px;

            padding: 14px 16px;

            border-radius: 14px;

            font-size: 13px;
            font-weight: 600;
            line-height: 1.6;
        }

        .admin-alert-success {
            border: 1px solid #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .admin-alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-grid {
            display: grid;

            margin-top: 24px;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 14px;
        }

        .summary-card {
            min-width: 0;

            padding: 20px;

            border:
                1px solid
                var(--border);

            border-radius: 19px;

            background: var(--white);

            box-shadow:
                0 12px 30px
                rgba(2, 132, 199, 0.06);
        }

        .summary-card span {
            display: block;

            color: var(--muted);

            font-size: 14px;
            font-weight: 600;
        }

        .summary-card strong {
            display: block;

            margin-top: 12px;

            color: var(--primary-dark);

            font-size: 29px;
            font-weight: 700;
            line-height: 1;
        }

        .summary-card.active strong {
            color: #059669;
        }

        .summary-card.pending strong {
            color: #d97706;
        }

        .summary-card.blocked strong {
            color: #dc2626;
        }

        /*
        |--------------------------------------------------------------------------
        | SELLER PANEL
        |--------------------------------------------------------------------------
        */

        .seller-panel {
            margin-top: 20px;

            padding: 21px;

            border:
                1px solid
                var(--border);

            border-radius: 21px;

            background: var(--white);

            box-shadow:
                0 13px 33px
                rgba(2, 132, 199, 0.06);
        }

        .panel-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;
        }

        .panel-header h2 {
            margin: 0;

            color: var(--dark);

            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;

            letter-spacing: -0.025em;
        }

        .panel-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 13px;
            font-weight: 400;
        }

        .seller-search {
            width: 270px;
            height: 44px;

            flex: 0 0 270px;

            padding: 0 13px;

            border:
                1px solid
                var(--border);

            border-radius: 13px;

            outline: none;

            background: var(--soft);
            color: var(--dark);

            font-size: 14px;
            font-weight: 400;
        }

        .seller-search:focus {
            border-color: var(--primary-light);

            box-shadow:
                0 0 0 4px
                rgba(14, 165, 233, 0.1);
        }

        .seller-list {
            display: grid;

            margin-top: 17px;

            gap: 14px;
        }

        .seller-card {
            min-width: 0;

            padding: 18px;

            border:
                1px solid
                var(--border);

            border-radius: 18px;

            background: var(--soft);
        }

        .seller-card.hidden {
            display: none;
        }

        .seller-card-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;
        }

        .seller-card-header > div {
            min-width: 0;
        }

        .seller-card h3 {
            margin: 0;

            overflow-wrap: anywhere;

            color: var(--dark);

            font-size: 20px;
            font-weight: 700;
            line-height: 1.3;

            letter-spacing: -0.02em;
        }

        .seller-meta {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 13px;
            font-weight: 400;
            line-height: 1.6;
        }

        .seller-status {
            display: inline-flex;

            min-height: 38px;

            flex: 0 0 auto;

            align-items: center;
            justify-content: center;

            padding: 9px 14px;

            border-radius: 999px;

            font-size: 11px;
            font-weight: 600;

            white-space: nowrap;
        }

        .seller-status-active {
            background: #d1fae5;
            color: #047857;
        }

        .seller-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .seller-status-blocked {
            background: #fee2e2;
            color: #b91c1c;
        }

        .seller-info-grid {
            display: grid;

            margin-top: 14px;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 10px;
        }

        .seller-info {
            min-width: 0;

            padding: 12px;

            border:
                1px solid
                #d5eef9;

            border-radius: 13px;

            background: var(--white);
        }

        .seller-info span,
        .seller-info strong {
            display: block;
        }

        .seller-info span {
            margin-bottom: 6px;

            color: var(--muted);

            font-size: 11px;
            font-weight: 500;
        }

        .seller-info strong {
            overflow-wrap: anywhere;

            color: var(--primary-dark);

            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }

        .seller-description {
            margin-top: 11px;

            padding: 13px;

            border-radius: 13px;

            background: var(--white);

            color: var(--text);

            font-size: 13px;
            font-weight: 400;
            line-height: 1.65;

            overflow-wrap: anywhere;
        }

        .seller-description strong {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .seller-actions {
            display: flex;

            margin-top: 13px;

            flex-wrap: wrap;

            gap: 9px;
        }

        .seller-actions form {
            margin: 0;
        }

        .empty-state {
            padding: 50px 20px;

            color: var(--muted);

            font-size: 14px;
            font-weight: 400;

            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .seller-modal {
            position: fixed;
            z-index: 1600;

            display: none;

            inset: 0;

            padding: 20px;

            align-items: center;
            justify-content: center;

            background:
                rgba(15, 23, 42, 0.55);

            backdrop-filter: blur(5px);
        }

        .seller-modal.open {
            display: flex;
        }

        .seller-modal-card {
            width: min(820px, 100%);
            max-height: calc(100vh - 40px);

            overflow-y: auto;

            border:
                1px solid
                var(--border);

            border-radius: 22px;

            background: var(--white);

            box-shadow:
                0 28px 70px
                rgba(15, 23, 42, 0.24);
        }

        .seller-modal-header {
            position: sticky;
            z-index: 2;
            top: 0;

            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;

            padding: 21px;

            border-bottom:
                1px solid
                var(--border);

            background: var(--white);
        }

        .seller-modal-header h2 {
            margin: 0;

            color: var(--dark);

            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
        }

        .seller-modal-header p {
            margin: 7px 0 0;

            color: var(--muted);

            font-size: 13px;
            font-weight: 400;
        }

        .seller-modal-close {
            display: inline-flex;

            width: 40px;
            height: 40px;

            cursor: pointer;

            align-items: center;
            justify-content: center;

            border:
                1px solid
                var(--border);

            border-radius: 50%;

            background: var(--soft);

            color: var(--primary-dark);

            font-size: 25px;
            font-weight: 400;
            line-height: 1;
        }

        .seller-modal-body {
            padding: 21px;
        }

        .seller-form-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 14px;
        }

        .seller-field {
            min-width: 0;
        }

        .seller-field-full {
            grid-column: 1 / -1;
        }

        .seller-field label {
            display: block;

            margin-bottom: 7px;

            color: var(--primary-dark);

            font-size: 13px;
            font-weight: 600;
        }

        .seller-field input,
        .seller-field select,
        .seller-field textarea {
            width: 100%;
            min-height: 44px;

            padding: 11px 13px;

            border:
                1px solid
                var(--border);

            border-radius: 13px;

            outline: none;

            background: var(--soft);
            color: var(--dark);

            font-size: 14px;
            font-weight: 400;
        }

        .seller-field textarea {
            min-height: 95px;
            resize: vertical;
        }

        .seller-field input:focus,
        .seller-field select:focus,
        .seller-field textarea:focus {
            border-color: var(--primary-light);

            box-shadow:
                0 0 0 4px
                rgba(14, 165, 233, 0.1);
        }

        .seller-form-actions {
            display: flex;

            margin-top: 18px;

            justify-content: flex-end;

            gap: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 1050px) {
            .summary-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .seller-info-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }

        @media screen and (max-width: 1024px) {
            .admin-sidebar {
                left:
                    calc(
                        -1 * var(--sidebar-width)
                    );

                transition: left 0.25s ease;
            }

            .admin-sidebar.open {
                left: 0;
            }

            .admin-overlay {
                position: fixed;
                z-index: 1150;

                display: block;

                visibility: hidden;

                inset: 0;

                background:
                    rgba(15, 23, 42, 0.45);

                opacity: 0;

                transition:
                    opacity 0.25s ease,
                    visibility 0.25s ease;
            }

            .admin-overlay.open {
                visibility: visible;
                opacity: 1;
            }

            .admin-topbar {
                left: 0;
                padding: 0 13px;
            }

            .admin-menu-toggle {
                display: inline-flex;

                width: 42px;
                height: 42px;

                flex: 0 0 42px;

                cursor: pointer;

                align-items: center;
                justify-content: center;

                border:
                    1px solid
                    var(--border);

                border-radius: 12px;

                background: #e7f7ff;

                color: var(--primary-dark);

                font-size: 22px;
            }

            .admin-topbar-title {
                flex: 1;
            }

            .admin-topbar-title h1 {
                font-size: 16px;
            }

            .admin-topbar-title p,
            .admin-clock,
            .admin-topbar-user {
                display: none;
            }

            .admin-main {
                width: 100%;
                margin-left: 0;

                padding:
                    calc(
                        var(--topbar-height)
                        + 22px
                    )
                    13px
                    40px;
            }

            .page-header,
            .panel-header,
            .seller-card-header {
                flex-direction: column;
            }

            .page-header > .admin-button,
            .seller-search,
            .seller-status {
                width: 100%;
            }

            .seller-search {
                flex-basis: auto;
            }

            .seller-form-grid {
                grid-template-columns: 1fr;
            }

            .seller-field-full {
                grid-column: auto;
            }
        }

        @media screen and (max-width: 480px) {
            .summary-grid,
            .seller-info-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 29px;
            }

            .page-description {
                font-size: 14px;
            }

            .seller-panel {
                padding: 16px;
            }

            .seller-card {
                padding: 15px;
            }

            .seller-actions,
            .seller-form-actions {
                flex-direction: column;
            }

            .seller-actions form,
            .seller-actions .admin-button,
            .seller-form-actions .admin-button {
                width: 100%;
            }

            .seller-modal {
                padding: 10px;
            }

            .seller-modal-card {
                max-height:
                    calc(100vh - 20px);

                border-radius: 18px;
            }

            .seller-modal-body,
            .seller-modal-header {
                padding: 17px;
            }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="admin-panel-page standalone-admin-page">

<div
    class="admin-overlay"
    id="adminSidebarOverlay"
></div>

<aside
    class="admin-sidebar"
    id="adminSidebar"
>
    <a
        href="<?= mitraEscape(
            $urls['dashboard']
        ); ?>"
        class="admin-brand"
    >
        <span class="admin-brand-logo">
            A
        </span>

        <span class="admin-brand-text">
            <strong>Laundry UMKM</strong>
            <span>Panel Admin</span>
        </span>
    </a>

    <section class="admin-profile">
        <strong>
            <?= mitraEscape(
                $currentUserName
            ); ?>
        </strong>

        <span>Administrator</span>
    </section>

    <nav class="admin-menu">

        <a
            href="<?= mitraEscape(
                $urls['dashboard']
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                <rect x="14" y="14" width="7" height="7" rx="1"></rect>
            </svg>

            Dashboard
        </a>

        <a
            href="<?= mitraEscape(
                $urls['users']
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>

            Kelola Pengguna
        </a>

        <a
            href="<?= mitraEscape(
                $urls['mitras']
            ); ?>"
            class="admin-menu-link active"
        >
            <svg viewBox="0 0 24 24">
                <path d="M3 9l2-5h14l2 5"></path>
                <path d="M5 13v7h14v-7"></path>
                <path d="M9 20v-5h6v5"></path>
                <path d="M3 9h18v4H3z"></path>
            </svg>

            Kelola Seller
        </a>

        <a
            href="<?= mitraEscape(
                $urls['staff']
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="7" r="4"></circle>
                <path d="M5.5 21a6.5 6.5 0 0 1 13 0"></path>
            </svg>

            Kelola Petugas
        </a>

        <a
            href="<?= mitraEscape(
                $urls['services']
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 2v4"></path>
                <path d="M12 18v4"></path>
                <path d="M2 12h4"></path>
                <path d="M18 12h4"></path>
            </svg>

            Layanan Laundry
        </a>

        <a
            href="<?= mitraEscape(
                $urls['orders']
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <path d="M8 6h13"></path>
                <path d="M8 12h13"></path>
                <path d="M8 18h13"></path>
                <path d="M3 6h.01"></path>
                <path d="M3 12h.01"></path>
                <path d="M3 18h.01"></path>
            </svg>

            Seluruh Pesanan
        </a>

        <a
            href="<?= mitraEscape(
                $urls['complaints']
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
            </svg>

            Keluhan Pelanggan
        </a>

        <div class="admin-divider"></div>

        <a
            href="<?= mitraEscape(
                $urls['home']
            ); ?>"
            class="
                admin-menu-link
                admin-home-link
            "
        >
            <svg viewBox="0 0 24 24">
                <path d="M3 11.5 12 4l9 7.5"></path>
                <path d="M5.5 10.5V20h13v-9.5"></path>
            </svg>

            Halaman Utama
        </a>

        <a
            href="<?= mitraEscape(
                $urls['logout']
            ); ?>"
            class="
                admin-menu-link
                admin-logout-link
            "
        >
            <svg viewBox="0 0 24 24">
                <path d="M10 17l5-5-5-5"></path>
                <path d="M15 12H3"></path>
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            </svg>

            Logout
        </a>

    </nav>
</aside>

<header class="admin-topbar">

    <button
        type="button"
        class="admin-menu-toggle"
        id="adminSidebarToggle"
        aria-label="Buka menu"
        aria-expanded="false"
    >
        ☰
    </button>

    <div class="admin-topbar-title">
        <h1>Admin Laundry</h1>

        <p>
            Kelola seller dan mitra laundry.
        </p>
    </div>

    <div class="admin-topbar-right">

        <span
            class="admin-clock"
            id="adminCurrentClock"
        >
            Memuat waktu.
        </span>

        <div class="admin-topbar-user">
            <strong>
                <?= mitraEscape(
                    $currentUserName
                ); ?>
            </strong>

            <span>Pengelola Laundry</span>
        </div>

        <span class="admin-avatar">
            <?= mitraEscape(
                $adminInitial
            ); ?>
        </span>

    </div>
</header>

<main class="admin-main">
    <div class="admin-container">

        <section class="page-header">

            <div>
                <p class="page-eyebrow">
                    Admin Panel
                </p>

                <h1 class="page-title">
                    Kelola Seller
                </h1>

                <p class="page-description">
                    Tambah, pantau, aktifkan, blokir,
                    dan hapus akun seller laundry.
                </p>
            </div>

            <button
                type="button"
                class="
                    admin-button
                    admin-button-primary
                "
                id="openSellerModal"
            >
                + Tambah Seller
            </button>

        </section>

        <?php if ($successMessage !== '') : ?>

            <div
                class="
                    admin-alert
                    admin-alert-success
                "
            >
                <?= mitraEscape(
                    $successMessage
                ); ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage !== '') : ?>

            <div
                class="
                    admin-alert
                    admin-alert-error
                "
            >
                <?= mitraEscape(
                    $errorMessage
                ); ?>
            </div>

        <?php endif; ?>

        <section class="summary-grid">

            <article class="summary-card">
                <span>Total Seller</span>

                <strong>
                    <?= $summary['total']; ?>
                </strong>
            </article>

            <article class="summary-card active">
                <span>Seller Aktif</span>

                <strong>
                    <?= $summary['active']; ?>
                </strong>
            </article>

            <article class="summary-card pending">
                <span>Menunggu</span>

                <strong>
                    <?= $summary['pending']; ?>
                </strong>
            </article>

            <article class="summary-card blocked">
                <span>Diblokir</span>

                <strong>
                    <?= $summary['blocked']; ?>
                </strong>
            </article>

        </section>

        <section class="seller-panel">

            <header class="panel-header">

                <div>
                    <h2>
                        Daftar Seller Laundry
                    </h2>

                    <p>
                        Seluruh mitra laundry yang
                        terdaftar pada sistem.
                    </p>
                </div>

                <input
                    type="search"
                    id="sellerSearch"
                    class="seller-search"
                    placeholder="Cari seller"
                    autocomplete="off"
                >

            </header>

            <?php if (empty($mitras)) : ?>

                <div class="empty-state">
                    Belum ada seller laundry.
                </div>

            <?php else : ?>

                <div class="seller-list">

                    <?php foreach ($mitras as $mitra) : ?>

                        <?php

                        $mitraStatus = strtolower(
                            (string) (
                                $mitra['status']
                                ?? 'pending'
                            )
                        );

                        $statusLabels = [
                            'active' => 'Aktif',
                            'pending' => 'Menunggu',
                            'blocked' => 'Diblokir'
                        ];

                        $searchText = strtolower(
                            implode(
                                ' ',
                                [
                                    $mitra['mitra_name']
                                        ?? '',
                                    $mitra['owner_name']
                                        ?? '',
                                    $mitra['account_email']
                                        ?? '',
                                    $mitra['phone']
                                        ?? '',
                                    $mitra['city']
                                        ?? ''
                                ]
                            )
                        );

                        ?>

                        <article
                            class="seller-card"
                            data-search="<?= mitraEscape(
                                $searchText
                            ); ?>"
                        >

                            <header class="seller-card-header">

                                <div>
                                    <h3>
                                        <?= mitraEscape(
                                            $mitra['mitra_name']
                                            ?? 'Seller Laundry'
                                        ); ?>
                                    </h3>

                                    <p class="seller-meta">
                                        Pemilik:
                                        <?= mitraEscape(
                                            $mitra['owner_name']
                                            ?? '-'
                                        ); ?>

                                        <br>

                                        Terdaftar:
                                        <?= mitraEscape(
                                            mitraDate(
                                                $mitra['created_at']
                                                ?? null
                                            )
                                        ); ?>
                                    </p>
                                </div>

                                <span
                                    class="
                                        seller-status
                                        seller-status-<?= mitraEscape(
                                            $mitraStatus
                                        ); ?>
                                    "
                                >
                                    <?= mitraEscape(
                                        $statusLabels[
                                            $mitraStatus
                                        ]
                                        ?? ucfirst(
                                            $mitraStatus
                                        )
                                    ); ?>
                                </span>

                            </header>

                            <div class="seller-info-grid">

                                <div class="seller-info">
                                    <span>Email</span>

                                    <strong>
                                        <?= mitraEscape(
                                            $mitra['account_email']
                                            ?? '-'
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Telepon</span>

                                    <strong>
                                        <?= mitraEscape(
                                            $mitra['phone']
                                            ?? '-'
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Kota</span>

                                    <strong>
                                        <?= mitraEscape(
                                            $mitra['city']
                                            ?? '-'
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Layanan</span>

                                    <strong>
                                        <?= (int) (
                                            $mitra['service_total']
                                            ?? 0
                                        ); ?>
                                        layanan
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Petugas</span>

                                    <strong>
                                        <?= (int) (
                                            $mitra['staff_total']
                                            ?? 0
                                        ); ?>
                                        petugas
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Pesanan</span>

                                    <strong>
                                        <?= (int) (
                                            $mitra['order_total']
                                            ?? 0
                                        ); ?>
                                        pesanan
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Biaya Pickup</span>

                                    <strong>
                                        <?= mitraEscape(
                                            mitraCurrency(
                                                $mitra['pickup_fee']
                                                ?? 0
                                            )
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="seller-info">
                                    <span>Biaya Delivery</span>

                                    <strong>
                                        <?= mitraEscape(
                                            mitraCurrency(
                                                $mitra['delivery_fee']
                                                ?? 0
                                            )
                                        ); ?>
                                    </strong>
                                </div>

                            </div>

                            <div class="seller-description">
                                <strong>Alamat:</strong>

                                <?= nl2br(
                                    mitraEscape(
                                        $mitra['address']
                                        ?? '-'
                                    )
                                ); ?>

                                <br><br>

                                <strong>Deskripsi:</strong>

                                <?= nl2br(
                                    mitraEscape(
                                        $mitra['description']
                                        ?? '-'
                                    )
                                ); ?>
                            </div>

                            <div class="seller-actions">

                                <?php if (
                                    $mitraStatus !== 'active'
                                ) : ?>

                                    <form method="post">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= mitraEscape(
                                                $_SESSION[
                                                    'seller_admin_csrf'
                                                ]
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="status"
                                        >

                                        <input
                                            type="hidden"
                                            name="mitra_id"
                                            value="<?= (int) (
                                                $mitra['id']
                                                ?? 0
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) (
                                                $mitra['user_id']
                                                ?? 0
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="active"
                                        >

                                        <button
                                            type="submit"
                                            class="
                                                admin-button
                                                admin-button-success
                                            "
                                        >
                                            Aktifkan
                                        </button>

                                    </form>

                                <?php endif; ?>

                                <?php if (
                                    $mitraStatus === 'active'
                                ) : ?>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Blokir seller ini?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= mitraEscape(
                                                $_SESSION[
                                                    'seller_admin_csrf'
                                                ]
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="status"
                                        >

                                        <input
                                            type="hidden"
                                            name="mitra_id"
                                            value="<?= (int) (
                                                $mitra['id']
                                                ?? 0
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) (
                                                $mitra['user_id']
                                                ?? 0
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="blocked"
                                        >

                                        <button
                                            type="submit"
                                            class="
                                                admin-button
                                                admin-button-warning
                                            "
                                        >
                                            Blokir
                                        </button>

                                    </form>

                                <?php endif; ?>

                                <?php if (
                                    $mitraStatus === 'pending'
                                ) : ?>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Tolak dan blokir seller ini?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= mitraEscape(
                                                $_SESSION[
                                                    'seller_admin_csrf'
                                                ]
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="status"
                                        >

                                        <input
                                            type="hidden"
                                            name="mitra_id"
                                            value="<?= (int) (
                                                $mitra['id']
                                                ?? 0
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) (
                                                $mitra['user_id']
                                                ?? 0
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="blocked"
                                        >

                                        <button
                                            type="submit"
                                            class="
                                                admin-button
                                                admin-button-warning
                                            "
                                        >
                                            Tolak
                                        </button>

                                    </form>

                                <?php endif; ?>

                                <form
                                    method="post"
                                    onsubmit="return confirm('Hapus seller secara permanen? Seller yang masih memiliki layanan, petugas, atau pesanan tidak dapat dihapus.');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= mitraEscape(
                                            $_SESSION[
                                                'seller_admin_csrf'
                                            ]
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="mitra_id"
                                        value="<?= (int) (
                                            $mitra['id']
                                            ?? 0
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) (
                                            $mitra['user_id']
                                            ?? 0
                                        ); ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="
                                            admin-button
                                            admin-button-danger
                                        "
                                    >
                                        Hapus Seller
                                    </button>

                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <div
                    class="empty-state"
                    id="sellerSearchEmpty"
                    hidden
                >
                    Seller tidak ditemukan.
                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<div
    class="seller-modal"
    id="sellerModal"
    aria-hidden="true"
>
    <section class="seller-modal-card">

        <header class="seller-modal-header">

            <div>
                <h2>Tambah Seller Laundry</h2>

                <p>
                    Buat akun seller dan data mitra
                    laundry baru.
                </p>
            </div>

            <button
                type="button"
                class="seller-modal-close"
                id="closeSellerModal"
                aria-label="Tutup"
            >
                ×
            </button>

        </header>

        <div class="seller-modal-body">

            <form method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= mitraEscape(
                        $_SESSION[
                            'seller_admin_csrf'
                        ]
                    ); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

                <div class="seller-form-grid">

                    <div class="seller-field">
                        <label for="account_name">
                            Nama Akun
                        </label>

                        <input
                            type="text"
                            id="account_name"
                            name="account_name"
                            placeholder="Nama pengguna seller"
                            required
                        >
                    </div>

                    <div class="seller-field">
                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="seller@email.com"
                            required
                        >
                    </div>

                    <div class="seller-field">
                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="6"
                            placeholder="Minimal 6 karakter"
                            required
                        >
                    </div>

                    <div class="seller-field">
                        <label for="status">
                            Status Awal
                        </label>

                        <select
                            id="status"
                            name="status"
                            required
                        >
                            <option value="active">
                                Aktif
                            </option>

                            <option value="pending">
                                Menunggu
                            </option>

                            <option value="blocked">
                                Diblokir
                            </option>
                        </select>
                    </div>

                    <div class="seller-field">
                        <label for="mitra_name">
                            Nama Laundry
                        </label>

                        <input
                            type="text"
                            id="mitra_name"
                            name="mitra_name"
                            placeholder="Nama usaha laundry"
                            required
                        >
                    </div>

                    <div class="seller-field">
                        <label for="owner_name">
                            Nama Pemilik
                        </label>

                        <input
                            type="text"
                            id="owner_name"
                            name="owner_name"
                            placeholder="Nama pemilik laundry"
                            required
                        >
                    </div>

                    <div class="seller-field">
                        <label for="phone">
                            Telepon
                        </label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            placeholder="Nomor telepon"
                        >
                    </div>

                    <div class="seller-field">
                        <label for="city">
                            Kota
                        </label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            placeholder="Kota usaha"
                        >
                    </div>

                    <div class="seller-field">
                        <label for="pickup_fee">
                            Biaya Pickup
                        </label>

                        <input
                            type="number"
                            id="pickup_fee"
                            name="pickup_fee"
                            min="0"
                            value="0"
                        >
                    </div>

                    <div class="seller-field">
                        <label for="delivery_fee">
                            Biaya Delivery
                        </label>

                        <input
                            type="number"
                            id="delivery_fee"
                            name="delivery_fee"
                            min="0"
                            value="0"
                        >
                    </div>

                    <div
                        class="
                            seller-field
                            seller-field-full
                        "
                    >
                        <label for="address">
                            Alamat
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="Alamat lengkap laundry"
                        ></textarea>
                    </div>

                    <div
                        class="
                            seller-field
                            seller-field-full
                        "
                    >
                        <label for="description">
                            Deskripsi
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Deskripsi singkat usaha laundry"
                        ></textarea>
                    </div>

                </div>

                <div class="seller-form-actions">

                    <button
                        type="button"
                        class="
                            admin-button
                            admin-button-outline
                        "
                        id="cancelSellerModal"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="
                            admin-button
                            admin-button-primary
                        "
                    >
                        Simpan Seller
                    </button>

                </div>

            </form>

        </div>

    </section>
</div>

<script>
(function () {
    const sidebar =
        document.getElementById(
            'adminSidebar'
        );

    const sidebarOverlay =
        document.getElementById(
            'adminSidebarOverlay'
        );

    const sidebarToggle =
        document.getElementById(
            'adminSidebarToggle'
        );

    function openSidebar() {
        if (!sidebar || !sidebarOverlay) {
            return;
        }

        sidebar.classList.add('open');
        sidebarOverlay.classList.add('open');

        if (sidebarToggle) {
            sidebarToggle.setAttribute(
                'aria-expanded',
                'true'
            );
        }
    }

    function closeSidebar() {
        if (!sidebar || !sidebarOverlay) {
            return;
        }

        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('open');

        if (sidebarToggle) {
            sidebarToggle.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener(
            'click',
            function () {
                if (
                    sidebar
                    && sidebar.classList.contains(
                        'open'
                    )
                ) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }
        );
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener(
            'click',
            closeSidebar
        );
    }

    window.addEventListener(
        'resize',
        function () {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        }
    );

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(
            function (link) {
                link.addEventListener(
                    'click',
                    function () {
                        if (window.innerWidth <= 1024) {
                            closeSidebar();
                        }
                    }
                );
            }
        );
    }

    document.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        }
    );

    const clock =
        document.getElementById(
            'adminCurrentClock'
        );

    function updateClock() {
        if (!clock) {
            return;
        }

        clock.textContent =
            new Intl.DateTimeFormat(
                'id-ID',
                {
                    weekday: 'short',
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            ).format(new Date());
    }

    updateClock();

    window.setInterval(
        updateClock,
        30000
    );

    const sellerSearch =
        document.getElementById(
            'sellerSearch'
        );

    const sellerCards =
        Array.from(
            document.querySelectorAll(
                '.seller-card'
            )
        );

    const sellerSearchEmpty =
        document.getElementById(
            'sellerSearchEmpty'
        );

    function filterSeller() {
        const keyword =
            sellerSearch
                ? sellerSearch.value
                    .trim()
                    .toLowerCase()
                : '';

        let visibleCount = 0;

        sellerCards.forEach(
            function (card) {
                const searchData =
                    (
                        card.dataset.search
                        || ''
                    ).toLowerCase();

                const visible =
                    keyword === ''
                    || searchData.includes(
                        keyword
                    );

                card.classList.toggle(
                    'hidden',
                    !visible
                );

                if (visible) {
                    visibleCount++;
                }
            }
        );

        if (sellerSearchEmpty) {
            sellerSearchEmpty.hidden =
                visibleCount > 0;
        }
    }

    if (sellerSearch) {
        sellerSearch.addEventListener(
            'input',
            filterSeller
        );
    }

    const modal =
        document.getElementById(
            'sellerModal'
        );

    const openModalButton =
        document.getElementById(
            'openSellerModal'
        );

    const closeModalButton =
        document.getElementById(
            'closeSellerModal'
        );

    const cancelModalButton =
        document.getElementById(
            'cancelSellerModal'
        );

    function openModal() {
        if (!modal) {
            return;
        }

        modal.classList.add('open');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.style.overflow =
            'hidden';

        window.setTimeout(
            function () {
                const firstInput =
                    modal.querySelector(
                        'input:not([type="hidden"])'
                    );

                if (firstInput) {
                    firstInput.focus();
                }
            },
            100
        );
    }

    function closeModal() {
        if (!modal) {
            return;
        }

        modal.classList.remove('open');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.style.overflow = '';
    }

    if (openModalButton) {
        openModalButton.addEventListener(
            'click',
            openModal
        );
    }

    if (closeModalButton) {
        closeModalButton.addEventListener(
            'click',
            closeModal
        );
    }

    if (cancelModalButton) {
        cancelModalButton.addEventListener(
            'click',
            closeModal
        );
    }

    if (modal) {
        modal.addEventListener(
            'click',
            function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            }
        );
    }

    document.addEventListener(
        'keydown',
        function (event) {
            if (
                event.key === 'Escape'
                && modal
                && modal.classList.contains(
                    'open'
                )
            ) {
                closeModal();
            }
        }
    );
})();
</script>

</body>
</html>