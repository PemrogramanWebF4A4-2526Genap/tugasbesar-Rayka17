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

$loginUrl =
    $baseUrl
    . '/src/views/public/login.php';

$homeUrl =
    $baseUrl
    . '/src/views/public/home.php';

$logoutUrl =
    $baseUrl
    . '/src/views/public/logout.php';

$dashboardUrl =
    $baseUrl
    . '/src/views/customer_service/dashboard.php';

/*
|--------------------------------------------------------------------------
| DATA SESSION
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
    ?? 'Customer Service Laundry';

$currentUserEmail =
    $_SESSION['email']
    ?? $_SESSION['user_email']
    ?? $sessionUser['email']
    ?? '';

$currentUserRole =
    $_SESSION['role']
    ?? $_SESSION['user_role']
    ?? $_SESSION['login_role']
    ?? $sessionUser['role']
    ?? '';

$currentUserRole = strtolower(
    trim((string) $currentUserRole)
);

/*
|--------------------------------------------------------------------------
| NORMALISASI ROLE
|--------------------------------------------------------------------------
*/

$roleAliases = [
    'customer-service' => 'customer_service',
    'customer service' => 'customer_service',
    'customerservice' => 'customer_service',
    'cs' => 'customer_service'
];

$currentUserRole =
    $roleAliases[$currentUserRole]
    ?? $currentUserRole;

/*
|--------------------------------------------------------------------------
| VALIDASI LOGIN
|--------------------------------------------------------------------------
*/

if (
    empty($currentUserId)
    || (int) $currentUserId < 1
) {
    header(
        'Location: ' . $loginUrl
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDASI ROLE
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'customer_service',
    'admin'
];

if (
    !in_array(
        $currentUserRole,
        $allowedRoles,
        true
    )
) {
    header(
        'Location: ' . $homeUrl
    );

    exit;
}

$currentUserId = (int) $currentUserId;

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function csEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function csStatusLabel(
    string $status
): string {
    $labels = [
        'pending' => 'Menunggu',
        'process' => 'Diproses',
        'done' => 'Selesai'
    ];

    return $labels[$status]
        ?? ucfirst($status);
}

function csFormatDate(
    $date
): string {
    if (
        empty($date)
        || $date === '0000-00-00 00:00:00'
    ) {
        return '-';
    }

    $timestamp = strtotime(
        (string) $date
    );

    if (!$timestamp) {
        return '-';
    }

    return date(
        'd/m/Y H:i',
        $timestamp
    );
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['cs_csrf_token'])
) {
    $_SESSION['cs_csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION['cs_success']
    ?? '';

$errorMessage =
    $_SESSION['cs_error']
    ?? '';

unset(
    $_SESSION['cs_success'],
    $_SESSION['cs_error']
);

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {
    $csrfToken =
        $_POST['csrf_token']
        ?? '';

    if (
        !is_string($csrfToken)
        || !hash_equals(
            $_SESSION['cs_csrf_token'],
            $csrfToken
        )
    ) {
        $_SESSION['cs_error'] =
            'Sesi formulir tidak valid. Silakan ulangi.';

        header(
            'Location: ' . $dashboardUrl
        );

        exit;
    }

    $action =
        $_POST['action']
        ?? '';

    $complaintId = (int) (
        $_POST['complaint_id']
        ?? 0
    );

    if ($complaintId < 1) {
        $_SESSION['cs_error'] =
            'Data keluhan tidak valid.';

        header(
            'Location: ' . $dashboardUrl
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CEK KELUHAN
    |--------------------------------------------------------------------------
    */

    $checkStatement = mysqli_prepare(
        $conn,
        "
            SELECT id
            FROM complaints
            WHERE id = ?
            LIMIT 1
        "
    );

    if (!$checkStatement) {
        $_SESSION['cs_error'] =
            'Sistem gagal memeriksa data keluhan.';

        header(
            'Location: ' . $dashboardUrl
        );

        exit;
    }

    mysqli_stmt_bind_param(
        $checkStatement,
        'i',
        $complaintId
    );

    mysqli_stmt_execute(
        $checkStatement
    );

    mysqli_stmt_store_result(
        $checkStatement
    );

    $complaintExists =
        mysqli_stmt_num_rows(
            $checkStatement
        ) === 1;

    mysqli_stmt_close(
        $checkStatement
    );

    if (!$complaintExists) {
        $_SESSION['cs_error'] =
            'Keluhan tidak ditemukan.';

        header(
            'Location: '
            . $dashboardUrl
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | KIRIM BALASAN
    |--------------------------------------------------------------------------
    */

    if ($action === 'reply') {
        $replyText = trim(
            (string) (
                $_POST['reply']
                ?? ''
            )
        );

        if ($replyText === '') {
            $_SESSION['cs_error'] =
                'Balasan tidak boleh kosong.';

            header(
                'Location: '
                . $dashboardUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        if (
            mb_strlen(
                $replyText
            ) > 3000
        ) {
            $_SESSION['cs_error'] =
                'Balasan maksimal 3.000 karakter.';

            header(
                'Location: '
                . $dashboardUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        mysqli_begin_transaction(
            $conn
        );

        try {
            $replyStatement =
                mysqli_prepare(
                    $conn,
                    "
                        INSERT INTO
                        complaint_replies
                        (
                            complaint_id,
                            replier_id,
                            reply,
                            created_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            NOW()
                        )
                    "
                );

            if (!$replyStatement) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $replyStatement,
                'iis',
                $complaintId,
                $currentUserId,
                $replyText
            );

            if (
                !mysqli_stmt_execute(
                    $replyStatement
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $replyStatement
                    )
                );
            }

            mysqli_stmt_close(
                $replyStatement
            );

            $updateStatement =
                mysqli_prepare(
                    $conn,
                    "
                        UPDATE complaints
                        SET
                            status =
                                CASE
                                    WHEN status = 'pending'
                                    THEN 'process'
                                    ELSE status
                                END,
                            reply_by = ?
                        WHERE id = ?
                    "
                );

            if (!$updateStatement) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $updateStatement,
                'ii',
                $currentUserId,
                $complaintId
            );

            if (
                !mysqli_stmt_execute(
                    $updateStatement
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $updateStatement
                    )
                );
            }

            mysqli_stmt_close(
                $updateStatement
            );

            mysqli_commit($conn);

            $_SESSION['cs_success'] =
                'Balasan berhasil dikirim.';
        } catch (Throwable $error) {
            mysqli_rollback($conn);

            $_SESSION['cs_error'] =
                'Balasan gagal disimpan: '
                . $error->getMessage();
        }

        header(
            'Location: '
            . $dashboardUrl
            . '#keluhan-'
            . $complaintId
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | UBAH STATUS
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_status') {
        $newStatus = strtolower(
            trim(
                (string) (
                    $_POST['status']
                    ?? ''
                )
            )
        );

        $allowedStatuses = [
            'pending',
            'process',
            'done'
        ];

        if (
            !in_array(
                $newStatus,
                $allowedStatuses,
                true
            )
        ) {
            $_SESSION['cs_error'] =
                'Status keluhan tidak valid.';

            header(
                'Location: '
                . $dashboardUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        if ($newStatus === 'done') {
            $statusStatement =
                mysqli_prepare(
                    $conn,
                    "
                        UPDATE complaints
                        SET
                            status = ?,
                            reply_by = ?,
                            closed_at = NOW()
                        WHERE id = ?
                    "
                );
        } else {
            $statusStatement =
                mysqli_prepare(
                    $conn,
                    "
                        UPDATE complaints
                        SET
                            status = ?,
                            reply_by = ?,
                            closed_at = NULL
                        WHERE id = ?
                    "
                );
        }

        if (!$statusStatement) {
            $_SESSION['cs_error'] =
                'Sistem gagal menyiapkan perubahan status.';

            header(
                'Location: '
                . $dashboardUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        mysqli_stmt_bind_param(
            $statusStatement,
            'sii',
            $newStatus,
            $currentUserId,
            $complaintId
        );

        if (
            mysqli_stmt_execute(
                $statusStatement
            )
        ) {
            $_SESSION['cs_success'] =
                'Status keluhan berhasil diperbarui.';
        } else {
            $_SESSION['cs_error'] =
                'Status keluhan gagal diperbarui.';
        }

        mysqli_stmt_close(
            $statusStatement
        );

        header(
            'Location: '
            . $dashboardUrl
            . '#keluhan-'
            . $complaintId
        );

        exit;
    }

    $_SESSION['cs_error'] =
        'Aksi tidak dikenali.';

    header(
        'Location: '
        . $dashboardUrl
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| RINGKASAN KELUHAN
|--------------------------------------------------------------------------
*/

$summary = [
    'total' => 0,
    'pending' => 0,
    'process' => 0,
    'done' => 0
];

$summaryQuery = mysqli_query(
    $conn,
    "
        SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN status = 'pending'
                    THEN 1
                    ELSE 0
                END
            ) AS total_pending,
            SUM(
                CASE
                    WHEN status = 'process'
                    THEN 1
                    ELSE 0
                END
            ) AS total_process,
            SUM(
                CASE
                    WHEN status = 'done'
                    THEN 1
                    ELSE 0
                END
            ) AS total_done
        FROM complaints
    "
);

if ($summaryQuery) {
    $summaryRow =
        mysqli_fetch_assoc(
            $summaryQuery
        );

    $summary['total'] = (int) (
        $summaryRow['total']
        ?? 0
    );

    $summary['pending'] = (int) (
        $summaryRow['total_pending']
        ?? 0
    );

    $summary['process'] = (int) (
        $summaryRow['total_process']
        ?? 0
    );

    $summary['done'] = (int) (
        $summaryRow['total_done']
        ?? 0
    );
}

/*
|--------------------------------------------------------------------------
| DAFTAR KELUHAN
|--------------------------------------------------------------------------
*/

$complaints = [];

$complaintsQuery = mysqli_query(
    $conn,
    "
        SELECT
            c.id,
            c.buyer_id,
            c.order_id,
            c.title,
            c.message,
            c.status,
            c.reply_by,
            c.closed_at,
            c.created_at,

            buyer.name AS buyer_name,
            buyer.email AS buyer_email,
            buyer.phone AS buyer_phone,

            replier.name AS replier_name,

            lo.customer_name,
            lo.phone AS order_phone,
            lo.status AS order_status,
            lo.total_price,

            ls.service_name

        FROM complaints AS c

        LEFT JOIN users AS buyer
            ON buyer.id = c.buyer_id

        LEFT JOIN users AS replier
            ON replier.id = c.reply_by

        LEFT JOIN laundry_orders AS lo
            ON lo.id = c.order_id

        LEFT JOIN laundry_services AS ls
            ON ls.id = lo.service_id

        ORDER BY
            CASE c.status
                WHEN 'pending' THEN 1
                WHEN 'process' THEN 2
                WHEN 'done' THEN 3
                ELSE 4
            END,
            c.created_at DESC,
            c.id DESC
    "
);

if ($complaintsQuery) {
    while (
        $complaint =
            mysqli_fetch_assoc(
                $complaintsQuery
            )
    ) {
        $complaints[] = $complaint;
    }
}

/*
|--------------------------------------------------------------------------
| DAFTAR BALASAN
|--------------------------------------------------------------------------
*/

$repliesByComplaint = [];

$repliesQuery = mysqli_query(
    $conn,
    "
        SELECT
            cr.id,
            cr.complaint_id,
            cr.replier_id,
            cr.reply,
            cr.created_at,
            u.name AS replier_name,
            u.role AS replier_role
        FROM complaint_replies AS cr

        LEFT JOIN users AS u
            ON u.id = cr.replier_id

        ORDER BY
            cr.created_at ASC,
            cr.id ASC
    "
);

if ($repliesQuery) {
    while (
        $reply =
            mysqli_fetch_assoc(
                $repliesQuery
            )
    ) {
        $replyComplaintId =
            (int) (
                $reply['complaint_id']
                ?? 0
            );

        if (
            !isset(
                $repliesByComplaint[
                    $replyComplaintId
                ]
            )
        ) {
            $repliesByComplaint[
                $replyComplaintId
            ] = [];
        }

        $repliesByComplaint[
            $replyComplaintId
        ][] = $reply;
    }
}

$userInitial = strtoupper(
    mb_substr(
        trim($currentUserName),
        0,
        1
    )
);

if ($userInitial === '') {
    $userInitial = 'C';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Laundry UMKM browser icon -->
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg?v=7">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon-32x32.png?v=7">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon-16x16.png?v=7">
    <link rel="shortcut icon" href="../../assets/images/favicon.ico?v=7">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/apple-touch-icon.png?v=7">
    <link rel="manifest" href="../../assets/images/site.webmanifest?v=7">
    <meta name="theme-color" content="#0ea5e9">

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    <title>
        Dashboard Customer Service | Laundry UMKM
    </title>

    <style>
        :root {
            --sidebar-width: 245px;
            --topbar-height: 76px;

            --primary: #0284c7;
            --primary-light: #0ea5e9;
            --primary-dark: #075985;

            --sidebar-bg: #063b54;
            --sidebar-dark: #032f44;

            --dark: #07152d;
            --text: #334155;
            --muted: #64748b;

            --border: #b6e4fa;
            --soft: #f4fbff;
            --blue-soft: #e7f7ff;
            --white: #ffffff;

            --success: #059669;
            --success-soft: #d1fae5;

            --warning: #d97706;
            --warning-soft: #fef3c7;

            --danger: #dc2626;
            --danger-soft: #fee2e2;
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
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            background:
                linear-gradient(
                    135deg,
                    #e4f7ff 0%,
                    #f8fdff 48%,
                    #c9f9ff 100%
                );
            color: var(--dark);
            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        a {
            color: inherit;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .cs-sidebar {
            position: fixed;
            z-index: 1200;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            padding: 20px 14px 25px;
            overflow-x: hidden;
            overflow-y: auto;
            background:
                linear-gradient(
                    180deg,
                    var(--sidebar-bg),
                    var(--sidebar-dark)
                );
            color: var(--white);
            box-shadow:
                8px 0 30px
                rgba(15, 23, 42, 0.13);
        }

        .cs-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .cs-sidebar::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background:
                rgba(255, 255, 255, 0.24);
        }

        .cs-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 7px 7px 20px;
            color: var(--white);
            text-decoration: none;
        }

        .cs-brand-logo {
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
                rgba(14, 165, 233, 0.25);
            color: var(--white);
        }

        .cs-brand-logo svg {
            width: 24px;
            height: 24px;
        }

        .cs-brand-text {
            min-width: 0;
        }

        .cs-brand-text strong,
        .cs-brand-text span {
            display: block;
        }

        .cs-brand-text strong {
            font-size: 15px;
            line-height: 1.2;
        }

        .cs-brand-text span {
            margin-top: 4px;
            color: #bae6fd;
            font-size: 11px;
        }

        .cs-profile {
            margin-bottom: 16px;
            padding: 14px 13px;
            border:
                1px solid
                rgba(186, 230, 253, 0.2);
            border-radius: 15px;
            background:
                rgba(255, 255, 255, 0.08);
        }

        .cs-profile strong,
        .cs-profile span {
            display: block;
        }

        .cs-profile strong {
            overflow: hidden;
            color: var(--white);
            font-size: 13px;
            line-height: 1.4;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cs-profile span {
            margin-top: 5px;
            color: #bae6fd;
            font-size: 11px;
        }

        .cs-sidebar-menu {
            display: grid;
            gap: 7px;
        }

        .cs-sidebar-link {
            display: flex;
            min-height: 45px;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border: 1px solid transparent;
            border-radius: 12px;
            color: #dbeafe;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease;
        }

        .cs-sidebar-link:hover,
        .cs-sidebar-link.active {
            border-color:
                rgba(186, 230, 253, 0.13);
            background:
                rgba(255, 255, 255, 0.14);
            color: var(--white);
        }

        .cs-sidebar-link svg {
            width: 19px;
            height: 19px;
            flex: 0 0 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .cs-sidebar-divider {
            height: 1px;
            margin: 10px 5px;
            background:
                rgba(186, 230, 253, 0.15);
        }

        .cs-sidebar-link.home-link {
            background:
                rgba(14, 165, 233, 0.11);
        }

        .cs-sidebar-link.logout-link {
            border-color: transparent;
            background: var(--white);
            color: var(--primary-dark);
        }

        .cs-sidebar-link.logout-link:hover {
            background: #e0f2fe;
            color: var(--primary-dark);
        }

        /*
        |--------------------------------------------------------------------------
        | TOPBAR
        |--------------------------------------------------------------------------
        */

        .cs-topbar {
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
                1px solid var(--border);
            background:
                rgba(255, 255, 255, 0.97);
            box-shadow:
                0 5px 20px
                rgba(2, 132, 199, 0.06);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .cs-topbar-title h1 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 20px;
            line-height: 1.2;
        }

        .cs-topbar-title p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .cs-topbar-right {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .cs-time {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--blue-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .cs-topbar-user {
            text-align: right;
        }

        .cs-topbar-user strong,
        .cs-topbar-user span {
            display: block;
        }

        .cs-topbar-user strong {
            color: #ec1670;
            font-size: 13px;
        }

        .cs-topbar-user span {
            margin-top: 4px;
            color: #ec1670;
            font-size: 12px;
        }

        .cs-avatar {
            display: inline-flex;
            width: 43px;
            height: 43px;
            flex: 0 0 43px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background:
                linear-gradient(
                    135deg,
                    var(--primary-light),
                    var(--primary)
                );
            color: var(--white);
            font-size: 14px;
            font-weight: 900;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE TOGGLE
        |--------------------------------------------------------------------------
        */

        .cs-sidebar-toggle {
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

        .cs-sidebar-toggle svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
        }

        .cs-sidebar-overlay {
            position: fixed;
            z-index: 1150;
            display: none;
            inset: 0;
            background:
                rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .cs-main {
            width:
                calc(
                    100% - var(--sidebar-width)
                );
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

        .cs-container {
            width: min(
                1100px,
                100%
            );
            margin: 0 auto;
        }

        .cs-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .cs-eyebrow {
            margin: 0 0 9px;
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
        }

        .cs-page-title {
            margin: 0;
            color: var(--dark);
            font-size: clamp(
                31px,
                4vw,
                40px
            );
            line-height: 1.08;
            letter-spacing: -1px;
        }

        .cs-page-description {
            margin: 11px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.65;
        }

        .cs-header-action {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--white);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .cs-header-action svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .cs-alert {
            display: flex;
            margin-top: 18px;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.55;
        }

        .cs-alert-success {
            border: 1px solid #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .cs-alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .cs-summary-grid {
            display: grid;
            margin-top: 25px;
            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );
            gap: 15px;
        }

        .cs-summary-card {
            min-width: 0;
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 19px;
            background:
                rgba(255, 255, 255, 0.97);
            box-shadow:
                0 12px 30px
                rgba(2, 132, 199, 0.07);
        }

        .cs-summary-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .cs-summary-icon {
            display: inline-flex;
            width: 35px;
            height: 35px;
            flex: 0 0 35px;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: var(--blue-soft);
            color: var(--primary);
        }

        .cs-summary-icon svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .cs-summary-value {
            display: block;
            margin-top: 13px;
            color: var(--primary-dark);
            font-size: 30px;
            font-weight: 900;
            line-height: 1;
        }

        .cs-summary-card.pending
        .cs-summary-value {
            color: var(--warning);
        }

        .cs-summary-card.process
        .cs-summary-value {
            color: var(--primary);
        }

        .cs-summary-card.done
        .cs-summary-value {
            color: var(--success);
        }

        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .cs-panel {
            margin-top: 20px;
            padding: 21px;
            border: 1px solid var(--border);
            border-radius: 21px;
            background:
                rgba(255, 255, 255, 0.97);
            box-shadow:
                0 13px 33px
                rgba(2, 132, 199, 0.07);
        }

        .cs-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .cs-panel-title {
            margin: 0;
            color: var(--dark);
            font-size: 23px;
            line-height: 1.2;
        }

        .cs-panel-description {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .cs-filter-wrapper {
            display: flex;
            margin-top: 18px;
            align-items: center;
            gap: 10px;
        }

        .cs-search {
            width: 100%;
            min-height: 44px;
            padding: 0 14px;
            border: 1px solid var(--border);
            border-radius: 13px;
            outline: none;
            background: var(--soft);
            color: var(--dark);
            font-size: 14px;
        }

        .cs-search:focus {
            border-color: var(--primary-light);
            box-shadow:
                0 0 0 4px
                rgba(14, 165, 233, 0.1);
        }

        .cs-filter-select {
            width: 190px;
            min-height: 44px;
            padding: 0 12px;
            border: 1px solid var(--border);
            border-radius: 13px;
            outline: none;
            background: var(--white);
            color: var(--dark);
            font-size: 13px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | COMPLAINT CARD
        |--------------------------------------------------------------------------
        */

        .cs-complaint-list {
            display: grid;
            margin-top: 17px;
            gap: 14px;
        }

        .cs-complaint-card {
            padding: 19px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--soft);
            scroll-margin-top:
                calc(
                    var(--topbar-height)
                    + 20px
                );
        }

        .cs-complaint-card.hidden {
            display: none;
        }

        .cs-complaint-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 17px;
        }

        .cs-complaint-number {
            margin: 0 0 8px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
        }

        .cs-complaint-title {
            margin: 0;
            color: var(--dark);
            font-size: 19px;
            line-height: 1.3;
        }

        .cs-complaint-meta {
            display: flex;
            margin-top: 9px;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 10px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .cs-status-badge {
            display: inline-flex;
            min-width: 86px;
            min-height: 39px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            padding: 9px 13px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .cs-status-pending {
            background: var(--warning-soft);
            color: #92400e;
        }

        .cs-status-process {
            background: var(--blue-soft);
            color: #0369a1;
        }

        .cs-status-done {
            background: var(--success-soft);
            color: #047857;
        }

        .cs-customer-grid {
            display: grid;
            margin-top: 15px;
            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );
            gap: 10px;
        }

        .cs-customer-item {
            min-width: 0;
            padding: 12px;
            border: 1px solid #d5eef9;
            border-radius: 13px;
            background: var(--white);
        }

        .cs-customer-item span,
        .cs-customer-item strong {
            display: block;
        }

        .cs-customer-item span {
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
        }

        .cs-customer-item strong {
            overflow-wrap: anywhere;
            color: var(--primary-dark);
            font-size: 13px;
            line-height: 1.45;
        }

        .cs-message-box {
            margin-top: 12px;
            padding: 14px;
            border-left:
                4px solid
                var(--primary-light);
            border-radius: 0 13px 13px 0;
            background: var(--white);
        }

        .cs-message-box strong {
            display: block;
            margin-bottom: 7px;
            color: var(--primary-dark);
            font-size: 11px;
        }

        .cs-message-box p {
            margin: 0;
            color: var(--text);
            font-size: 13px;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        /*
        |--------------------------------------------------------------------------
        | REPLIES
        |--------------------------------------------------------------------------
        */

        .cs-replies {
            display: grid;
            margin-top: 13px;
            gap: 9px;
        }

        .cs-replies-heading {
            margin: 0;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .cs-reply-card {
            padding: 12px 13px;
            border: 1px solid #d5eef9;
            border-radius: 13px;
            background: #edf9ff;
        }

        .cs-reply-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .cs-reply-header strong {
            color: var(--primary-dark);
            font-size: 12px;
        }

        .cs-reply-header time {
            color: var(--muted);
            font-size: 10px;
        }

        .cs-reply-card p {
            margin: 8px 0 0;
            color: var(--text);
            font-size: 13px;
            line-height: 1.65;
            white-space: pre-wrap;
        }

        /*
        |--------------------------------------------------------------------------
        | FORMS
        |--------------------------------------------------------------------------
        */

        .cs-action-grid {
            display: grid;
            margin-top: 14px;
            grid-template-columns:
                minmax(0, 1fr)
                230px;
            gap: 11px;
        }

        .cs-form-card {
            padding: 13px;
            border: 1px solid #d5eef9;
            border-radius: 14px;
            background: var(--white);
        }

        .cs-form-label {
            display: block;
            margin-bottom: 7px;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 900;
        }

        .cs-textarea {
            width: 100%;
            min-height: 92px;
            padding: 11px 12px;
            resize: vertical;
            border: 1px solid var(--border);
            border-radius: 11px;
            outline: none;
            background: var(--soft);
            color: var(--dark);
            font-size: 13px;
            line-height: 1.55;
        }

        .cs-textarea:focus,
        .cs-status-select:focus {
            border-color: var(--primary-light);
            box-shadow:
                0 0 0 3px
                rgba(14, 165, 233, 0.1);
        }

        .cs-status-select {
            width: 100%;
            min-height: 43px;
            padding: 0 10px;
            border: 1px solid var(--border);
            border-radius: 11px;
            outline: none;
            background: var(--soft);
            color: var(--dark);
            font-size: 13px;
        }

        .cs-submit-button {
            display: inline-flex;
            width: 100%;
            min-height: 42px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            margin-top: 9px;
            padding: 10px 14px;
            border: 0;
            border-radius: 999px;
            background:
                linear-gradient(
                    135deg,
                    var(--primary-light),
                    #2563eb
                );
            box-shadow:
                0 9px 20px
                rgba(2, 132, 199, 0.16);
            color: var(--white);
            font-size: 12px;
            font-weight: 900;
        }

        .cs-submit-button:hover {
            filter: brightness(0.97);
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .cs-empty {
            padding: 50px 20px;
            text-align: center;
        }

        .cs-empty-icon {
            display: inline-flex;
            width: 64px;
            height: 64px;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border-radius: 20px;
            background: var(--blue-soft);
            color: var(--primary);
        }

        .cs-empty-icon svg {
            width: 31px;
            height: 31px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .cs-empty h3 {
            margin: 0;
            font-size: 20px;
        }

        .cs-empty p {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLET
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 1000px) {
            .cs-summary-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .cs-customer-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 760px) {
            :root {
                --topbar-height: 68px;
            }

            .cs-sidebar {
                z-index: 1400;
                width: min(
                    285px,
                    86vw
                );
                transform:
                    translateX(-105%);
                transition:
                    transform 0.25s ease;
            }

            .cs-sidebar.open {
                transform:
                    translateX(0);
            }

            .cs-sidebar-overlay.open {
                display: block;
            }

            .cs-topbar {
                z-index: 1300;
                left: 0;
                min-height:
                    var(--topbar-height);
                padding: 0 13px;
            }

            .cs-sidebar-toggle {
                display: inline-flex;
            }

            .cs-topbar-title {
                flex: 1;
                min-width: 0;
            }

            .cs-topbar-title h1 {
                overflow: hidden;
                font-size: 16px;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .cs-topbar-title p {
                display: none;
            }

            .cs-time {
                display: none;
            }

            .cs-topbar-user {
                display: none;
            }

            .cs-avatar {
                width: 40px;
                height: 40px;
                flex-basis: 40px;
            }

            .cs-main {
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

            .cs-page-header {
                flex-direction: column;
            }

            .cs-header-action {
                width: 100%;
            }

            .cs-panel {
                padding: 16px;
                border-radius: 18px;
            }

            .cs-filter-wrapper {
                align-items: stretch;
                flex-direction: column;
            }

            .cs-filter-select {
                width: 100%;
            }

            .cs-complaint-header {
                flex-direction: column;
            }

            .cs-status-badge {
                min-width: 0;
                width: 100%;
                min-height: 38px;
                border-radius: 12px;
            }

            .cs-customer-grid {
                grid-template-columns:
                    minmax(0, 1fr);
            }

            .cs-action-grid {
                grid-template-columns:
                    minmax(0, 1fr);
            }
        }

        @media screen and (max-width: 480px) {
            .cs-summary-grid {
                grid-template-columns:
                    minmax(0, 1fr);
            }

            .cs-page-title {
                font-size: 30px;
            }

            .cs-complaint-card {
                padding: 14px;
                border-radius: 15px;
            }

            .cs-reply-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div
    class="cs-sidebar-overlay"
    id="csSidebarOverlay"
></div>

<aside
    class="cs-sidebar"
    id="csSidebar"
>
    <a
        href="<?= csEscape(
            $dashboardUrl
        ); ?>"
        class="cs-brand"
    >
        <span class="cs-brand-logo">
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
            </svg>
        </span>

        <span class="cs-brand-text">
            <strong>
                Laundry UMKM
            </strong>

            <span>
                Panel Customer Service
            </span>
        </span>
    </a>

    <section class="cs-profile">
        <strong>
            <?= csEscape(
                $currentUserName
            ); ?>
        </strong>

        <span>
            Customer Service
        </span>
    </section>

    <nav class="cs-sidebar-menu">

        <a
            href="#dashboard"
            class="
                cs-sidebar-link
                active
            "
        >
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

            <span>
                Dashboard
            </span>
        </a>

        <a
            href="#keluhan"
            class="cs-sidebar-link"
        >
            <svg viewBox="0 0 24 24">
                <path
                    d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                ></path>

                <path d="M8 9h8"></path>
                <path d="M8 13h5"></path>
            </svg>

            <span>
                Keluhan Pelanggan
            </span>
        </a>

        <div class="cs-sidebar-divider"></div>

        <a
            href="<?= csEscape(
                $homeUrl
            ); ?>"
            class="
                cs-sidebar-link
                home-link
            "
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

            <span>
                Halaman Utama
            </span>
        </a>

        <a
            href="<?= csEscape(
                $logoutUrl
            ); ?>"
            class="
                cs-sidebar-link
                logout-link
            "
        >
            <svg viewBox="0 0 24 24">
                <path
                    d="M10 17l5-5-5-5"
                ></path>

                <path d="M15 12H3"></path>

                <path
                    d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"
                ></path>
            </svg>

            <span>
                Logout
            </span>
        </a>

    </nav>
</aside>

<header class="cs-topbar">
    <button
        type="button"
        class="cs-sidebar-toggle"
        id="csSidebarToggle"
        aria-label="Buka menu"
        aria-expanded="false"
    >
        <svg
            id="csSidebarToggleIcon"
            viewBox="0 0 24 24"
        >
            <path d="M4 7h16"></path>
            <path d="M4 12h16"></path>
            <path d="M4 17h16"></path>
        </svg>
    </button>

    <div class="cs-topbar-title">
        <h1>
            Customer Service Laundry
        </h1>

        <p>
            Kelola keluhan dan komunikasi pelanggan.
        </p>
    </div>

    <div class="cs-topbar-right">
        <span
            class="cs-time"
            id="csCurrentTime"
        >
            Memuat waktu...
        </span>

        <div class="cs-topbar-user">
            <strong>
                <?= csEscape(
                    $currentUserName
                ); ?>
            </strong>

            <span>
                Customer Service
            </span>
        </div>

        <span class="cs-avatar">
            <?= csEscape(
                $userInitial
            ); ?>
        </span>
    </div>
</header>

<main
    class="cs-main"
    id="dashboard"
>
    <div class="cs-container">

        <section class="cs-page-header">
            <div>
                <p class="cs-eyebrow">
                    Customer Service Panel
                </p>

                <h2 class="cs-page-title">
                    Dashboard Customer Service
                </h2>

                <p class="cs-page-description">
                    Pantau keluhan, kirim balasan,
                    dan perbarui status penyelesaian pelanggan.
                </p>
            </div>

            <a
                href="<?= csEscape(
                    $homeUrl
                ); ?>"
                class="cs-header-action"
            >
                <svg viewBox="0 0 24 24">
                    <path
                        d="M3 11.5 12 4l9 7.5"
                    ></path>

                    <path
                        d="M5.5 10.5V20h13v-9.5"
                    ></path>
                </svg>

                Halaman Utama
            </a>
        </section>

        <?php if (
            $successMessage !== ''
        ): ?>

            <div
                class="
                    cs-alert
                    cs-alert-success
                "
            >
                <?= csEscape(
                    $successMessage
                ); ?>
            </div>

        <?php endif; ?>

        <?php if (
            $errorMessage !== ''
        ): ?>

            <div
                class="
                    cs-alert
                    cs-alert-error
                "
            >
                <?= csEscape(
                    $errorMessage
                ); ?>
            </div>

        <?php endif; ?>

        <section class="cs-summary-grid">

            <article class="cs-summary-card">
                <div class="cs-summary-label">
                    <span>
                        Total Keluhan
                    </span>

                    <span class="cs-summary-icon">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                            ></path>
                        </svg>
                    </span>
                </div>

                <strong class="cs-summary-value">
                    <?= $summary['total']; ?>
                </strong>
            </article>

            <article
                class="
                    cs-summary-card
                    pending
                "
            >
                <div class="cs-summary-label">
                    <span>
                        Menunggu
                    </span>

                    <span class="cs-summary-icon">
                        <svg viewBox="0 0 24 24">
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                            ></circle>

                            <path d="M12 7v5"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                    </span>
                </div>

                <strong class="cs-summary-value">
                    <?= $summary['pending']; ?>
                </strong>
            </article>

            <article
                class="
                    cs-summary-card
                    process
                "
            >
                <div class="cs-summary-label">
                    <span>
                        Diproses
                    </span>

                    <span class="cs-summary-icon">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M20 12a8 8 0 1 1-2.3-5.7"
                            ></path>

                            <path d="M20 4v6h-6"></path>
                        </svg>
                    </span>
                </div>

                <strong class="cs-summary-value">
                    <?= $summary['process']; ?>
                </strong>
            </article>

            <article
                class="
                    cs-summary-card
                    done
                "
            >
                <div class="cs-summary-label">
                    <span>
                        Selesai
                    </span>

                    <span class="cs-summary-icon">
                        <svg viewBox="0 0 24 24">
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                            ></circle>

                            <path
                                d="m8 12 2.5 2.5L16 9"
                            ></path>
                        </svg>
                    </span>
                </div>

                <strong class="cs-summary-value">
                    <?= $summary['done']; ?>
                </strong>
            </article>

        </section>

        <section
            class="cs-panel"
            id="keluhan"
        >
            <header class="cs-panel-header">
                <div>
                    <h3 class="cs-panel-title">
                        Keluhan Pelanggan
                    </h3>

                    <p class="cs-panel-description">
                        Daftar keluhan terbaru dari pelanggan Laundry UMKM.
                    </p>
                </div>
            </header>

            <div class="cs-filter-wrapper">
                <input
                    type="search"
                    id="complaintSearch"
                    class="cs-search"
                    placeholder="Cari nama pelanggan, judul, nomor order, atau isi keluhan"
                    autocomplete="off"
                >

                <select
                    id="complaintStatusFilter"
                    class="cs-filter-select"
                >
                    <option value="all">
                        Semua Status
                    </option>

                    <option value="pending">
                        Menunggu
                    </option>

                    <option value="process">
                        Diproses
                    </option>

                    <option value="done">
                        Selesai
                    </option>
                </select>
            </div>

            <?php if (
                empty($complaints)
            ): ?>

                <div class="cs-empty">
                    <span class="cs-empty-icon">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                            ></path>

                            <path d="M8 9h8"></path>
                            <path d="M8 13h5"></path>
                        </svg>
                    </span>

                    <h3>
                        Belum ada keluhan
                    </h3>

                    <p>
                        Keluhan yang dibuat pelanggan akan tampil di halaman ini.
                    </p>
                </div>

            <?php else: ?>

                <div
                    class="cs-complaint-list"
                    id="complaintList"
                >

                    <?php foreach (
                        $complaints
                        as $complaint
                    ): ?>

                        <?php
                        $complaintId =
                            (int) (
                                $complaint['id']
                                ?? 0
                            );

                        $complaintStatus =
                            strtolower(
                                (string) (
                                    $complaint['status']
                                    ?? 'pending'
                                )
                            );

                        $buyerName =
                            $complaint['buyer_name']
                            ?? $complaint['customer_name']
                            ?? 'Pelanggan Laundry';

                        $buyerEmail =
                            $complaint['buyer_email']
                            ?? '-';

                        $buyerPhone =
                            $complaint['buyer_phone']
                            ?? $complaint['order_phone']
                            ?? '-';

                        $serviceName =
                            $complaint['service_name']
                            ?? 'Layanan Laundry';

                        $orderId =
                            (int) (
                                $complaint['order_id']
                                ?? 0
                            );

                        $searchData = strtolower(
                            implode(
                                ' ',
                                [
                                    $buyerName,
                                    $buyerEmail,
                                    $buyerPhone,
                                    $complaint['title']
                                        ?? '',
                                    $complaint['message']
                                        ?? '',
                                    $serviceName,
                                    $orderId
                                ]
                            )
                        );
                        ?>

                        <article
                            class="cs-complaint-card"
                            id="keluhan-<?= $complaintId; ?>"
                            data-status="<?= csEscape(
                                $complaintStatus
                            ); ?>"
                            data-search="<?= csEscape(
                                $searchData
                            ); ?>"
                        >
                            <header class="cs-complaint-header">
                                <div>
                                    <p class="cs-complaint-number">
                                        Keluhan #<?= $complaintId; ?>

                                        <?php if (
                                            $orderId > 0
                                        ): ?>
                                            • Order #<?= $orderId; ?>
                                        <?php endif; ?>
                                    </p>

                                    <h4 class="cs-complaint-title">
                                        <?= csEscape(
                                            $complaint['title']
                                            ?? 'Keluhan Pelanggan'
                                        ); ?>
                                    </h4>

                                    <div class="cs-complaint-meta">
                                        <span>
                                            <?= csEscape(
                                                $buyerName
                                            ); ?>
                                        </span>

                                        <span>
                                            <?= csEscape(
                                                $serviceName
                                            ); ?>
                                        </span>

                                        <span>
                                            <?= csEscape(
                                                csFormatDate(
                                                    $complaint['created_at']
                                                    ?? null
                                                )
                                            ); ?>
                                        </span>
                                    </div>
                                </div>

                                <span
                                    class="
                                        cs-status-badge
                                        cs-status-<?= csEscape(
                                            $complaintStatus
                                        ); ?>
                                    "
                                >
                                    <?= csEscape(
                                        csStatusLabel(
                                            $complaintStatus
                                        )
                                    ); ?>
                                </span>
                            </header>

                            <div class="cs-customer-grid">

                                <div class="cs-customer-item">
                                    <span>
                                        Pelanggan
                                    </span>

                                    <strong>
                                        <?= csEscape(
                                            $buyerName
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="cs-customer-item">
                                    <span>
                                        Email
                                    </span>

                                    <strong>
                                        <?= csEscape(
                                            $buyerEmail
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="cs-customer-item">
                                    <span>
                                        Nomor Telepon
                                    </span>

                                    <strong>
                                        <?= csEscape(
                                            $buyerPhone
                                        ); ?>
                                    </strong>
                                </div>

                            </div>

                            <section class="cs-message-box">
                                <strong>
                                    Isi Keluhan
                                </strong>

                                <p><?= csEscape(
                                    $complaint['message']
                                    ?? 'Tidak ada isi keluhan.'
                                ); ?></p>
                            </section>

                            <?php if (
                                !empty(
                                    $repliesByComplaint[
                                        $complaintId
                                    ]
                                )
                            ): ?>

                                <section class="cs-replies">
                                    <p class="cs-replies-heading">
                                        Riwayat Balasan
                                    </p>

                                    <?php foreach (
                                        $repliesByComplaint[
                                            $complaintId
                                        ]
                                        as $reply
                                    ): ?>

                                        <article class="cs-reply-card">
                                            <header class="cs-reply-header">
                                                <strong>
                                                    <?= csEscape(
                                                        $reply['replier_name']
                                                        ?? 'Customer Service'
                                                    ); ?>
                                                </strong>

                                                <time>
                                                    <?= csEscape(
                                                        csFormatDate(
                                                            $reply['created_at']
                                                            ?? null
                                                        )
                                                    ); ?>
                                                </time>
                                            </header>

                                            <p><?= csEscape(
                                                $reply['reply']
                                                ?? ''
                                            ); ?></p>
                                        </article>

                                    <?php endforeach; ?>
                                </section>

                            <?php endif; ?>

                            <div class="cs-action-grid">

                                <form
                                    method="post"
                                    class="cs-form-card"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= csEscape(
                                            $_SESSION[
                                                'cs_csrf_token'
                                            ]
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reply"
                                    >

                                    <input
                                        type="hidden"
                                        name="complaint_id"
                                        value="<?= $complaintId; ?>"
                                    >

                                    <label
                                        class="cs-form-label"
                                        for="reply-<?= $complaintId; ?>"
                                    >
                                        Balas Keluhan
                                    </label>

                                    <textarea
                                        id="reply-<?= $complaintId; ?>"
                                        name="reply"
                                        class="cs-textarea"
                                        maxlength="3000"
                                        placeholder="Tuliskan balasan kepada pelanggan"
                                        required
                                    ></textarea>

                                    <button
                                        type="submit"
                                        class="cs-submit-button"
                                    >
                                        Kirim Balasan
                                    </button>
                                </form>

                                <form
                                    method="post"
                                    class="cs-form-card"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= csEscape(
                                            $_SESSION[
                                                'cs_csrf_token'
                                            ]
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update_status"
                                    >

                                    <input
                                        type="hidden"
                                        name="complaint_id"
                                        value="<?= $complaintId; ?>"
                                    >

                                    <label
                                        class="cs-form-label"
                                        for="status-<?= $complaintId; ?>"
                                    >
                                        Status Keluhan
                                    </label>

                                    <select
                                        id="status-<?= $complaintId; ?>"
                                        name="status"
                                        class="cs-status-select"
                                        required
                                    >
                                        <option
                                            value="pending"
                                            <?= $complaintStatus ===
                                                'pending'
                                                ? 'selected'
                                                : ''; ?>
                                        >
                                            Menunggu
                                        </option>

                                        <option
                                            value="process"
                                            <?= $complaintStatus ===
                                                'process'
                                                ? 'selected'
                                                : ''; ?>
                                        >
                                            Diproses
                                        </option>

                                        <option
                                            value="done"
                                            <?= $complaintStatus ===
                                                'done'
                                                ? 'selected'
                                                : ''; ?>
                                        >
                                            Selesai
                                        </option>
                                    </select>

                                    <button
                                        type="submit"
                                        class="cs-submit-button"
                                    >
                                        Simpan Status
                                    </button>
                                </form>

                            </div>
                        </article>

                    <?php endforeach; ?>

                </div>

                <div
                    class="cs-empty"
                    id="complaintFilterEmpty"
                    hidden
                >
                    <span class="cs-empty-icon">
                        <svg viewBox="0 0 24 24">
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                            ></circle>

                            <path d="m20 20-4-4"></path>
                        </svg>
                    </span>

                    <h3>
                        Keluhan tidak ditemukan
                    </h3>

                    <p>
                        Ubah kata pencarian atau filter status.
                    </p>
                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<script>
(function () {
    /*
    |--------------------------------------------------------------------------
    | SIDEBAR MOBILE
    |--------------------------------------------------------------------------
    */

    const sidebar =
        document.getElementById(
            'csSidebar'
        );

    const overlay =
        document.getElementById(
            'csSidebarOverlay'
        );

    const toggle =
        document.getElementById(
            'csSidebarToggle'
        );

    const toggleIcon =
        document.getElementById(
            'csSidebarToggleIcon'
        );

    const menuIcon = `
        <path d="M4 7h16"></path>
        <path d="M4 12h16"></path>
        <path d="M4 17h16"></path>
    `;

    const closeIcon = `
        <path d="M6 6l12 12"></path>
        <path d="M18 6 6 18"></path>
    `;

    function openSidebar() {
        if (
            !sidebar
            || !overlay
            || !toggle
        ) {
            return;
        }

        sidebar.classList.add('open');
        overlay.classList.add('open');

        toggle.setAttribute(
            'aria-expanded',
            'true'
        );

        if (toggleIcon) {
            toggleIcon.innerHTML =
                closeIcon;
        }

        document.body.style.overflow =
            'hidden';
    }

    function closeSidebar() {
        if (
            !sidebar
            || !overlay
            || !toggle
        ) {
            return;
        }

        sidebar.classList.remove('open');
        overlay.classList.remove('open');

        toggle.setAttribute(
            'aria-expanded',
            'false'
        );

        if (toggleIcon) {
            toggleIcon.innerHTML =
                menuIcon;
        }

        document.body.style.overflow = '';
    }

    if (
        sidebar
        && overlay
        && toggle
    ) {
        toggle.addEventListener(
            'click',
            function () {
                if (
                    sidebar.classList.contains(
                        'open'
                    )
                ) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
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
                            <= 760
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
                    window.innerWidth
                    > 760
                ) {
                    closeSidebar();
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | WAKTU
    |--------------------------------------------------------------------------
    */

    const currentTimeElement =
        document.getElementById(
            'csCurrentTime'
        );

    function updateCurrentTime() {
        if (!currentTimeElement) {
            return;
        }

        const now = new Date();

        currentTimeElement.textContent =
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
            ).format(now);
    }

    updateCurrentTime();

    setInterval(
        updateCurrentTime,
        30000
    );

    /*
    |--------------------------------------------------------------------------
    | FILTER KELUHAN
    |--------------------------------------------------------------------------
    */

    const searchInput =
        document.getElementById(
            'complaintSearch'
        );

    const statusFilter =
        document.getElementById(
            'complaintStatusFilter'
        );

    const complaintCards =
        Array.from(
            document.querySelectorAll(
                '.cs-complaint-card'
            )
        );

    const filterEmpty =
        document.getElementById(
            'complaintFilterEmpty'
        );

    function filterComplaints() {
        if (
            complaintCards.length === 0
        ) {
            return;
        }

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const statusValue =
            statusFilter
                ? statusFilter.value
                : 'all';

        let visibleCount = 0;

        complaintCards.forEach(
            function (card) {
                const cardSearch =
                    (
                        card.dataset.search
                        || ''
                    ).toLowerCase();

                const cardStatus =
                    card.dataset.status
                    || '';

                const matchesSearch =
                    searchValue === ''
                    || cardSearch.includes(
                        searchValue
                    );

                const matchesStatus =
                    statusValue === 'all'
                    || cardStatus ===
                        statusValue;

                const visible =
                    matchesSearch
                    && matchesStatus;

                card.classList.toggle(
                    'hidden',
                    !visible
                );

                if (visible) {
                    visibleCount += 1;
                }
            }
        );

        if (filterEmpty) {
            filterEmpty.hidden =
                visibleCount > 0;
        }
    }

    if (searchInput) {
        searchInput.addEventListener(
            'input',
            filterComplaints
        );
    }

    if (statusFilter) {
        statusFilter.addEventListener(
            'change',
            filterComplaints
        );
    }
})();
</script>

</body>
</html>