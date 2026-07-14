<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__
    . '/../../config/database.php';
require_once __DIR__
    . '/../../config/route-helper.php';

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl = appBaseUrl();

function adminComplaintUrl(
    string $path
): string {
    global $baseUrl;

    return $baseUrl
        . '/'
        . ltrim($path, '/');
}

function adminComplaintEscape(
    $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function adminComplaintDate(
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

function adminComplaintStatusLabel(
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

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/

$loginUrl = adminComplaintUrl(
    'src/views/public/login.php'
);

$logoutUrl = adminComplaintUrl(
    'src/views/public/logout.php'
);

$homeUrl = adminComplaintUrl(
    'src/views/public/home.php'
);

$dashboardUrl = adminComplaintUrl(
    'src/views/admin/dashboard.php'
);

$usersUrl = adminComplaintUrl(
    'src/views/admin/users.php'
);

$mitrasUrl = adminComplaintUrl(
    'src/views/admin/mitras.php'
);

$staffUrl = adminComplaintUrl(
    'src/views/admin/staff.php'
);

$servicesUrl = adminComplaintUrl(
    'src/views/admin/services.php'
);

$ordersUrl = adminComplaintUrl(
    'src/views/admin/orders.php'
);

$complaintsUrl = adminComplaintUrl(
    'src/views/admin/complaints.php'
);

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

$sessionUser = [];

if (
    isset($_SESSION['user'])
    && is_array($_SESSION['user'])
) {
    $sessionUser = $_SESSION['user'];
} elseif (
    isset($_SESSION['auth_user'])
    && is_array($_SESSION['auth_user'])
) {
    $sessionUser = $_SESSION['auth_user'];
}

$currentUserId = (int) (
    $sessionUser['id']
    ?? $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? 0
);

$currentUserName = trim(
    (string) (
        $sessionUser['name']
        ?? $_SESSION['name']
        ?? $_SESSION['user_name']
        ?? 'Admin Laundry'
    )
);

$currentUserRole = strtolower(
    trim(
        (string) (
            $sessionUser['role']
            ?? $_SESSION['role']
            ?? ''
        )
    )
);

if ($currentUserId < 1) {
    header(
        'Location: '
        . $loginUrl
    );

    exit;
}

if ($currentUserRole !== 'admin') {
    header(
        'Location: '
        . $homeUrl
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION[
            'admin_complaint_csrf'
        ]
    )
) {
    $_SESSION[
        'admin_complaint_csrf'
    ] = bin2hex(
        random_bytes(32)
    );
}

/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION[
        'admin_complaint_success'
    ]
    ?? '';

$errorMessage =
    $_SESSION[
        'admin_complaint_error'
    ]
    ?? '';

unset(
    $_SESSION[
        'admin_complaint_success'
    ],
    $_SESSION[
        'admin_complaint_error'
    ]
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
    $csrfToken = (string) (
        $_POST['csrf_token']
        ?? ''
    );

    $sessionToken = (string) (
        $_SESSION[
            'admin_complaint_csrf'
        ]
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
        $_SESSION[
            'admin_complaint_error'
        ] = 'Sesi formulir tidak valid.';

        header(
            'Location: '
            . $complaintsUrl
        );

        exit;
    }

    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );

    $complaintId = (int) (
        $_POST['complaint_id']
        ?? 0
    );

    if ($complaintId < 1) {
        $_SESSION[
            'admin_complaint_error'
        ] = 'Data keluhan tidak valid.';

        header(
            'Location: '
            . $complaintsUrl
        );

        exit;
    }

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
        $_SESSION[
            'admin_complaint_error'
        ] = 'Sistem gagal memeriksa keluhan.';

        header(
            'Location: '
            . $complaintsUrl
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
        $_SESSION[
            'admin_complaint_error'
        ] = 'Keluhan tidak ditemukan.';

        header(
            'Location: '
            . $complaintsUrl
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | BALAS KELUHAN
    |--------------------------------------------------------------------------
    */

    if ($action === 'reply') {
        $reply = trim(
            (string) (
                $_POST['reply']
                ?? ''
            )
        );

        $replyLength =
            function_exists('mb_strlen')
                ? mb_strlen($reply)
                : strlen($reply);

        if ($reply === '') {
            $_SESSION[
                'admin_complaint_error'
            ] = 'Balasan tidak boleh kosong.';

            header(
                'Location: '
                . $complaintsUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        if ($replyLength > 3000) {
            $_SESSION[
                'admin_complaint_error'
            ] = 'Balasan maksimal 3.000 karakter.';

            header(
                'Location: '
                . $complaintsUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        mysqli_begin_transaction(
            $conn
        );

        try {
            $replyStatement = mysqli_prepare(
                $conn,
                "
                    INSERT INTO complaint_replies
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
                $reply
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

            $updateComplaint =
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

            if (!$updateComplaint) {
                throw new Exception(
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $updateComplaint,
                'ii',
                $currentUserId,
                $complaintId
            );

            if (
                !mysqli_stmt_execute(
                    $updateComplaint
                )
            ) {
                throw new Exception(
                    mysqli_stmt_error(
                        $updateComplaint
                    )
                );
            }

            mysqli_stmt_close(
                $updateComplaint
            );

            mysqli_commit($conn);

            $_SESSION[
                'admin_complaint_success'
            ] = 'Balasan berhasil dikirim.';
        } catch (Throwable $error) {
            mysqli_rollback($conn);

            $_SESSION[
                'admin_complaint_error'
            ] = 'Balasan gagal disimpan.';
        }

        header(
            'Location: '
            . $complaintsUrl
            . '#keluhan-'
            . $complaintId
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
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
            $_SESSION[
                'admin_complaint_error'
            ] = 'Status keluhan tidak valid.';

            header(
                'Location: '
                . $complaintsUrl
                . '#keluhan-'
                . $complaintId
            );

            exit;
        }

        if ($newStatus === 'done') {
            $statusStatement = mysqli_prepare(
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
            $statusStatement = mysqli_prepare(
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
            $_SESSION[
                'admin_complaint_error'
            ] = 'Sistem gagal mengubah status.';

            header(
                'Location: '
                . $complaintsUrl
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
            $_SESSION[
                'admin_complaint_success'
            ] = 'Status keluhan berhasil diperbarui.';
        } else {
            $_SESSION[
                'admin_complaint_error'
            ] = 'Status keluhan gagal diperbarui.';
        }

        mysqli_stmt_close(
            $statusStatement
        );

        header(
            'Location: '
            . $complaintsUrl
            . '#keluhan-'
            . $complaintId
        );

        exit;
    }

    $_SESSION[
        'admin_complaint_error'
    ] = 'Aksi tidak dikenali.';

    header(
        'Location: '
        . $complaintsUrl
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| RINGKASAN
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

            ls.service_name,

            lm.mitra_name

        FROM complaints AS c

        LEFT JOIN users AS buyer
            ON buyer.id = c.buyer_id

        LEFT JOIN users AS replier
            ON replier.id = c.reply_by

        LEFT JOIN laundry_orders AS lo
            ON lo.id = c.order_id

        LEFT JOIN laundry_services AS ls
            ON ls.id = lo.service_id

        LEFT JOIN laundry_mitras AS lm
            ON lm.id = lo.mitra_id

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
        $complaints[] =
            $complaint;
    }
}

/*
|--------------------------------------------------------------------------
| BALASAN
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
        $replyComplaintId = (int) (
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

/*
|--------------------------------------------------------------------------
| AVATAR
|--------------------------------------------------------------------------
*/

if (
    function_exists(
        'mb_substr'
    )
) {
    $userInitial =
        mb_substr(
            $currentUserName,
            0,
            1
        );
} else {
    $userInitial =
        substr(
            $currentUserName,
            0,
            1
        );
}

$userInitial = strtoupper(
    $userInitial !== ''
        ? $userInitial
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

    <title>
        Keluhan Pelanggan | Admin Laundry
    </title>

    <style>
        :root {
            --admin-sidebar-width: 245px;
            --admin-topbar-height: 76px;

            --admin-sidebar: #043e57;
            --admin-sidebar-dark: #032f44;

            --admin-primary: #0284c7;
            --admin-light: #0ea5e9;
            --admin-dark-blue: #075985;

            --admin-dark: #07152d;
            --admin-text: #334155;
            --admin-muted: #64748b;

            --admin-border: #b6e4fa;
            --admin-soft: #f4fbff;
            --admin-white: #ffffff;

            --admin-success: #059669;
            --admin-warning: #d97706;
            --admin-danger: #dc2626;
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
            min-width: 0;
            min-height: 100vh;

            margin: 0;

            overflow-x: hidden;

            background:
                linear-gradient(
                    135deg,
                    #e7f8ff 0%,
                    #f9fdff 52%,
                    #caf9ff 100%
                );

            color:
                var(--admin-dark);

            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            font-size: 14px;
            line-height: 1.5;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .admin-sidebar {
            position: fixed;

            z-index: 1500;

            top: 0;
            bottom: 0;
            left: 0;

            width:
                var(--admin-sidebar-width);

            height: 100vh;

            padding:
                20px
                14px
                24px;

            overflow-x: hidden;
            overflow-y: auto;

            background:
                linear-gradient(
                    180deg,
                    var(--admin-sidebar),
                    var(--admin-sidebar-dark)
                );

            color:
                var(--admin-white);

            box-shadow:
                8px 0 30px
                rgba(15, 23, 42, 0.13);

            scrollbar-width: none;
        }

        .admin-sidebar::-webkit-scrollbar {
            display: none;
        }

        .admin-brand {
            display: flex;

            align-items: center;

            gap: 11px;

            padding:
                7px
                7px
                20px;

            color:
                var(--admin-white);

            text-decoration: none;
        }

        .admin-logo {
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
                    var(--admin-light),
                    #2563eb
                );

            color:
                var(--admin-white);

            font-size: 15px;
            font-weight: 800;
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

            font-size: 16px;
            font-weight: 800;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-brand-text span {
            margin-top: 4px;

            color: #bae6fd;

            font-size: 11px;
        }

        .admin-profile {
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

        .admin-profile strong,
        .admin-profile span {
            display: block;
        }

        .admin-profile strong {
            overflow: hidden;

            font-size: 13px;
            font-weight: 700;

            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-profile span {
            margin-top: 5px;

            color: #bae6fd;

            font-size: 11px;
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

            padding:
                11px
                12px;

            border:
                1px solid
                transparent;

            border-radius: 12px;

            color: #dbeafe;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
        }

        .admin-menu-link:hover,
        .admin-menu-link.active {
            border-color:
                rgba(186, 230, 253, 0.14);

            background:
                rgba(255, 255, 255, 0.14);

            color:
                var(--admin-white);
        }

        .admin-menu-link svg {
            width: 19px;
            height: 19px;

            flex: 0 0 19px;

            fill: none;

            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .admin-divider {
            height: 1px;

            margin:
                10px
                5px;

            background:
                rgba(186, 230, 253, 0.15);
        }

        .admin-home-link {
            background:
                rgba(14, 165, 233, 0.11);
        }

        .admin-logout-link {
            background:
                var(--admin-white);

            color:
                var(--admin-dark-blue);
        }

        /*
        |--------------------------------------------------------------------------
        | TOPBAR
        |--------------------------------------------------------------------------
        */

        .admin-topbar {
            position: fixed;

            z-index: 1300;

            top: 0;
            right: 0;
            left:
                var(--admin-sidebar-width);

            display: flex;

            min-height:
                var(--admin-topbar-height);

            align-items: center;
            justify-content: space-between;

            gap: 18px;

            padding:
                0
                28px;

            border-bottom:
                1px solid
                var(--admin-border);

            background:
                rgba(255, 255, 255, 0.98);
        }

        .admin-topbar-title h1 {
            margin: 0;

            color:
                var(--admin-dark-blue);

            font-size: 20px;
            font-weight: 800;
        }

        .admin-topbar-title p {
            margin:
                6px
                0
                0;

            color:
                var(--admin-muted);

            font-size: 12px;
        }

        .admin-topbar-right {
            display: flex;

            align-items: center;

            gap: 13px;
        }

        .admin-clock {
            padding:
                10px
                14px;

            border:
                1px solid
                var(--admin-border);

            border-radius: 999px;

            background: #e7f7ff;

            color:
                var(--admin-dark-blue);

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .admin-user-label {
            text-align: right;
        }

        .admin-user-label strong,
        .admin-user-label span {
            display: block;

            color: #ec1670;
        }

        .admin-user-label strong {
            font-size: 13px;
        }

        .admin-user-label span {
            margin-top: 3px;

            font-size: 12px;
        }

        .admin-avatar {
            display: inline-flex;

            width: 43px;
            height: 43px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    var(--admin-light),
                    var(--admin-primary)
                );

            color:
                var(--admin-white);

            font-weight: 800;
        }

        .admin-toggle,
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
                    100% -
                    var(--admin-sidebar-width)
                );

            min-height: 100vh;

            margin-left:
                var(--admin-sidebar-width);

            padding:
                calc(
                    var(--admin-topbar-height)
                    + 28px
                )
                26px
                55px;
        }

        .admin-container {
            width:
                min(
                    1100px,
                    100%
                );

            margin:
                0
                auto;
        }

        .admin-eyebrow {
            margin:
                0
                0
                8px;

            color:
                var(--admin-primary);

            font-size: 13px;
            font-weight: 800;
        }

        .admin-title {
            margin: 0;

            font-size: 36px;
            font-weight: 800;
            line-height: 1.1;

            letter-spacing: -1px;
        }

        .admin-description {
            margin:
                10px
                0
                0;

            color:
                var(--admin-muted);

            font-size: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .admin-alert {
            margin-top: 18px;

            padding:
                14px
                16px;

            border-radius: 14px;

            font-size: 13px;
        }

        .admin-alert-success {
            border:
                1px solid
                #a7f3d0;

            background: #ecfdf5;

            color: #047857;
        }

        .admin-alert-error {
            border:
                1px solid
                #fecaca;

            background: #fef2f2;

            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .admin-summary-grid {
            display: grid;

            margin-top: 24px;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 14px;
        }

        .admin-summary-card {
            padding: 20px;

            border:
                1px solid
                var(--admin-border);

            border-radius: 19px;

            background:
                var(--admin-white);
        }

        .admin-summary-card span {
            display: block;

            color:
                var(--admin-muted);

            font-size: 12px;
            font-weight: 700;
        }

        .admin-summary-card strong {
            display: block;

            margin-top: 12px;

            color:
                var(--admin-dark-blue);

            font-size: 30px;
            font-weight: 800;
        }

        .admin-summary-card.pending strong {
            color:
                var(--admin-warning);
        }

        .admin-summary-card.process strong {
            color:
                var(--admin-primary);
        }

        .admin-summary-card.done strong {
            color:
                var(--admin-success);
        }

        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .admin-panel {
            margin-top: 20px;

            padding: 21px;

            border:
                1px solid
                var(--admin-border);

            border-radius: 21px;

            background:
                var(--admin-white);
        }

        .admin-panel-header h2 {
            margin: 0;

            font-size: 24px;
            font-weight: 800;
        }

        .admin-panel-header p {
            margin:
                8px
                0
                0;

            color:
                var(--admin-muted);

            font-size: 13px;
        }

        .admin-filter {
            display: flex;

            margin-top: 18px;

            gap: 10px;
        }

        .admin-filter input,
        .admin-filter select {
            min-height: 44px;

            border:
                1px solid
                var(--admin-border);

            border-radius: 13px;

            outline: none;

            background:
                var(--admin-soft);

            color:
                var(--admin-dark);
        }

        .admin-filter input {
            width: 100%;

            padding:
                0
                14px;
        }

        .admin-filter select {
            width: 190px;

            padding:
                0
                12px;
        }

        /*
        |--------------------------------------------------------------------------
        | COMPLAINT
        |--------------------------------------------------------------------------
        */

        .complaint-list {
            display: grid;

            margin-top: 17px;

            gap: 14px;
        }

        .complaint-card {
            padding: 19px;

            border:
                1px solid
                var(--admin-border);

            border-radius: 18px;

            background:
                var(--admin-soft);

            scroll-margin-top:
                calc(
                    var(--admin-topbar-height)
                    + 20px
                );
        }

        .complaint-card.hidden {
            display: none;
        }

        .complaint-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 16px;
        }

        .complaint-number {
            margin:
                0
                0
                8px;

            color:
                var(--admin-primary);

            font-size: 12px;
            font-weight: 800;
        }

        .complaint-title {
            margin: 0;

            font-size: 20px;
            font-weight: 800;
        }

        .complaint-meta {
            margin:
                8px
                0
                0;

            color:
                var(--admin-muted);

            font-size: 12px;
        }

        .complaint-status {
            display: inline-flex;

            min-height: 39px;

            align-items: center;
            justify-content: center;

            padding:
                9px
                14px;

            border-radius: 999px;

            font-size: 11px;
            font-weight: 800;

            white-space: nowrap;
        }

        .complaint-status-pending {
            background: #fef3c7;

            color: #92400e;
        }

        .complaint-status-process {
            background: #e0f2fe;

            color: #0369a1;
        }

        .complaint-status-done {
            background: #d1fae5;

            color: #047857;
        }

        .complaint-info-grid {
            display: grid;

            margin-top: 14px;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 10px;
        }

        .complaint-info {
            padding: 12px;

            border:
                1px solid
                #d5eef9;

            border-radius: 13px;

            background:
                var(--admin-white);
        }

        .complaint-info span,
        .complaint-info strong {
            display: block;
        }

        .complaint-info span {
            margin-bottom: 6px;

            color:
                var(--admin-muted);

            font-size: 10px;
            font-weight: 700;
        }

        .complaint-info strong {
            overflow-wrap: anywhere;

            color:
                var(--admin-dark-blue);

            font-size: 13px;
        }

        .complaint-message {
            margin-top: 12px;

            padding: 14px;

            border-left:
                4px solid
                var(--admin-light);

            border-radius:
                0
                13px
                13px
                0;

            background:
                var(--admin-white);
        }

        .complaint-message strong {
            display: block;

            margin-bottom: 7px;

            color:
                var(--admin-dark-blue);

            font-size: 11px;
        }

        .complaint-message p {
            margin: 0;

            color:
                var(--admin-text);

            font-size: 13px;
            line-height: 1.7;

            white-space: pre-wrap;
        }

        .reply-list {
            display: grid;

            margin-top: 13px;

            gap: 9px;
        }

        .reply-list-title {
            margin: 0;

            color:
                var(--admin-dark-blue);

            font-size: 12px;
            font-weight: 800;
        }

        .reply-card {
            padding:
                12px
                13px;

            border:
                1px solid
                #d5eef9;

            border-radius: 13px;

            background: #edf9ff;
        }

        .reply-header {
            display: flex;

            justify-content: space-between;

            gap: 10px;
        }

        .reply-header strong {
            color:
                var(--admin-dark-blue);

            font-size: 12px;
        }

        .reply-header time {
            color:
                var(--admin-muted);

            font-size: 10px;
        }

        .reply-card p {
            margin:
                8px
                0
                0;

            color:
                var(--admin-text);

            font-size: 13px;
            line-height: 1.65;

            white-space: pre-wrap;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .complaint-action-grid {
            display: grid;

            margin-top: 14px;

            grid-template-columns:
                minmax(0, 1fr)
                230px;

            gap: 11px;
        }

        .complaint-form {
            padding: 13px;

            border:
                1px solid
                #d5eef9;

            border-radius: 14px;

            background:
                var(--admin-white);
        }

        .complaint-form label {
            display: block;

            margin-bottom: 7px;

            color:
                var(--admin-dark-blue);

            font-size: 11px;
            font-weight: 800;
        }

        .complaint-form textarea,
        .complaint-form select {
            width: 100%;

            border:
                1px solid
                var(--admin-border);

            border-radius: 11px;

            outline: none;

            background:
                var(--admin-soft);
        }

        .complaint-form textarea {
            min-height: 92px;

            padding:
                11px
                12px;

            resize: vertical;
        }

        .complaint-form select {
            min-height: 43px;

            padding:
                0
                10px;
        }

        .complaint-button {
            display: inline-flex;

            width: 100%;
            min-height: 42px;

            cursor: pointer;

            align-items: center;
            justify-content: center;

            margin-top: 9px;

            padding:
                10px
                14px;

            border: 0;

            border-radius: 999px;

            background:
                linear-gradient(
                    135deg,
                    var(--admin-light),
                    #2563eb
                );

            color:
                var(--admin-white);

            font-size: 12px;
            font-weight: 800;
        }

        .admin-empty {
            padding:
                50px
                20px;

            color:
                var(--admin-muted);

            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 1000px) {
            .admin-summary-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .complaint-info-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }

        @media screen and (max-width: 1024px) {
            :root {
                --admin-topbar-height: 68px;
            }

            .admin-sidebar {
                z-index: 1600;

                width:
                    min(
                        285px,
                        86vw
                    );

                transform:
                    translateX(-105%);

                transition:
                    transform
                    0.25s ease;
            }

            .admin-sidebar.open {
                transform:
                    translateX(0);
            }

            .admin-overlay {
                position: fixed;

                z-index: 1550;

                display: none;

                inset: 0;

                background:
                    rgba(15, 23, 42, 0.52);
            }

            .admin-overlay.open {
                display: block;
            }

            .admin-topbar {
                z-index: 1500;

                left: 0;

                padding:
                    0
                    13px;
            }

            .admin-toggle {
                display: inline-flex;

                width: 42px;
                height: 42px;

                cursor: pointer;

                align-items: center;
                justify-content: center;

                border:
                    1px solid
                    var(--admin-border);

                border-radius: 12px;

                background:
                    var(--admin-white);

                color:
                    var(--admin-dark-blue);

                font-size: 20px;
            }

            .admin-topbar-title {
                flex: 1;
            }

            .admin-topbar-title h1 {
                font-size: 16px;
            }

            .admin-topbar-title p,
            .admin-clock,
            .admin-user-label {
                display: none;
            }

            .admin-main {
                width: 100%;

                margin-left: 0;

                padding:
                    calc(
                        var(--admin-topbar-height)
                        + 22px
                    )
                    13px
                    40px;
            }

            .admin-filter {
                flex-direction: column;
            }

            .admin-filter select {
                width: 100%;
            }

            .complaint-header {
                flex-direction: column;
            }

            .complaint-status {
                width: 100%;

                border-radius: 12px;
            }

            .complaint-action-grid {
                grid-template-columns:
                    minmax(0, 1fr);
            }
        }

        @media screen and (max-width: 480px) {
            .admin-summary-grid,
            .complaint-info-grid {
                grid-template-columns:
                    minmax(0, 1fr);
            }

            .admin-title {
                font-size: 30px;
            }

            .admin-panel {
                padding: 16px;
            }

            .complaint-card {
                padding: 15px;
            }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="admin-panel-page standalone-admin-page">

<div
    class="admin-overlay"
    id="adminOverlay"
></div>

<aside
    class="admin-sidebar"
    id="adminSidebar"
>
    <a
        href="<?= adminComplaintEscape(
            $dashboardUrl
        ); ?>"
        class="admin-brand"
    >
        <span class="admin-logo">
            A
        </span>

        <span class="admin-brand-text">
            <strong>
                Laundry UMKM
            </strong>

            <span>
                Panel Admin
            </span>
        </span>
    </a>

    <section class="admin-profile">
        <strong>
            <?= adminComplaintEscape(
                $currentUserName
            ); ?>
        </strong>

        <span>
            Administrator
        </span>
    </section>

    <nav class="admin-menu">

        <a
            href="<?= adminComplaintEscape(
                $dashboardUrl
            ); ?>"
            class="admin-menu-link"
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

            Dashboard
        </a>

        <a
            href="<?= adminComplaintEscape(
                $usersUrl
            ); ?>"
            class="admin-menu-link"
        >
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

            Kelola Pengguna
        </a>

        <a
            href="<?= adminComplaintEscape(
                $mitrasUrl
            ); ?>"
            class="admin-menu-link"
        >
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

            Kelola Seller
        </a>

        <a
            href="<?= adminComplaintEscape(
                $staffUrl
            ); ?>"
            class="admin-menu-link"
        >
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

            Kelola Petugas
        </a>

        <a
            href="<?= adminComplaintEscape(
                $servicesUrl
            ); ?>"
            class="admin-menu-link"
        >
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

            Layanan Laundry
        </a>

        <a
            href="<?= adminComplaintEscape(
                $ordersUrl
            ); ?>"
            class="admin-menu-link"
        >
            <svg viewBox="0 0 24 24">
                <path d="M8 6h12"></path>
                <path d="M8 12h12"></path>
                <path d="M8 18h12"></path>

                <path d="M3 6h.01"></path>
                <path d="M3 12h.01"></path>
                <path d="M3 18h.01"></path>
            </svg>

            Seluruh Pesanan
        </a>

        <a
            href="<?= adminComplaintEscape(
                $complaintsUrl
            ); ?>"
            class="
                admin-menu-link
                active
            "
        >
            <svg viewBox="0 0 24 24">
                <path
                    d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                ></path>

                <path d="M8 9h8"></path>
                <path d="M8 13h5"></path>
            </svg>

            Keluhan Pelanggan
        </a>

        <div class="admin-divider"></div>

        <a
            href="<?= adminComplaintEscape(
                $homeUrl
            ); ?>"
            class="
                admin-menu-link
                admin-home-link
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

            Halaman Utama
        </a>

        <a
            href="<?= adminComplaintEscape(
                $logoutUrl
            ); ?>"
            class="
                admin-menu-link
                admin-logout-link
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

            Logout
        </a>

    </nav>
</aside>

<header class="admin-topbar">

    <button
        type="button"
        class="admin-toggle"
        id="adminToggle"
        aria-label="Buka atau tutup menu admin"
        aria-expanded="false"
        aria-controls="adminSidebar"
    >
        ☰
    </button>

    <div class="admin-topbar-title">
        <h1>
            Admin Laundry
        </h1>

        <p>
            Kelola layanan, pesanan, dan user laundry.
        </p>
    </div>

    <div class="admin-topbar-right">

        <span
            class="admin-clock"
            id="adminClock"
        >
            Memuat waktu...
        </span>

        <div class="admin-user-label">
            <strong>
                <?= adminComplaintEscape(
                    $currentUserName
                ); ?>
            </strong>

            <span>
                Pengelola Laundry
            </span>
        </div>

        <span class="admin-avatar">
            <?= adminComplaintEscape(
                $userInitial
            ); ?>
        </span>

    </div>

</header>

<main class="admin-main">
    <div class="admin-container">

        <p class="admin-eyebrow">
            Admin Panel
        </p>

        <h1 class="admin-title">
            Keluhan Pelanggan
        </h1>

        <p class="admin-description">
            Pantau, balas, dan selesaikan keluhan pelanggan Laundry UMKM.
        </p>

        <?php if (
            $successMessage !== ''
        ): ?>

            <div
                class="
                    admin-alert
                    admin-alert-success
                "
            >
                <?= adminComplaintEscape(
                    $successMessage
                ); ?>
            </div>

        <?php endif; ?>

        <?php if (
            $errorMessage !== ''
        ): ?>

            <div
                class="
                    admin-alert
                    admin-alert-error
                "
            >
                <?= adminComplaintEscape(
                    $errorMessage
                ); ?>
            </div>

        <?php endif; ?>

        <section class="admin-summary-grid">

            <article class="admin-summary-card">
                <span>
                    Total Keluhan
                </span>

                <strong>
                    <?= $summary['total']; ?>
                </strong>
            </article>

            <article
                class="
                    admin-summary-card
                    pending
                "
            >
                <span>
                    Menunggu
                </span>

                <strong>
                    <?= $summary['pending']; ?>
                </strong>
            </article>

            <article
                class="
                    admin-summary-card
                    process
                "
            >
                <span>
                    Diproses
                </span>

                <strong>
                    <?= $summary['process']; ?>
                </strong>
            </article>

            <article
                class="
                    admin-summary-card
                    done
                "
            >
                <span>
                    Selesai
                </span>

                <strong>
                    <?= $summary['done']; ?>
                </strong>
            </article>

        </section>

        <section class="admin-panel">

            <header class="admin-panel-header">
                <h2>
                    Daftar Keluhan
                </h2>

                <p>
                    Seluruh keluhan yang dibuat pelanggan.
                </p>
            </header>

            <div class="admin-filter">

                <input
                    type="search"
                    id="complaintSearch"
                    placeholder="Cari pelanggan, judul, order, atau isi keluhan"
                    autocomplete="off"
                >

                <select id="complaintStatusFilter">
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

                <div class="admin-empty">
                    <h3>
                        Belum ada keluhan
                    </h3>

                    <p>
                        Keluhan pelanggan akan tampil di halaman ini.
                    </p>
                </div>

            <?php else: ?>

                <div class="complaint-list">

                    <?php foreach (
                        $complaints
                        as $complaint
                    ): ?>

                        <?php

                        $complaintId = (int) (
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

                        $mitraName =
                            $complaint['mitra_name']
                            ?? 'Mitra Laundry';

                        $orderId = (int) (
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
                                    $serviceName,
                                    $mitraName,
                                    $complaint['title']
                                        ?? '',
                                    $complaint['message']
                                        ?? '',
                                    $orderId
                                ]
                            )
                        );

                        ?>

                        <article
                            class="complaint-card"
                            id="keluhan-<?= $complaintId; ?>"
                            data-status="<?= adminComplaintEscape(
                                $complaintStatus
                            ); ?>"
                            data-search="<?= adminComplaintEscape(
                                $searchData
                            ); ?>"
                        >

                            <header class="complaint-header">

                                <div>
                                    <p class="complaint-number">
                                        Keluhan #<?= $complaintId; ?>

                                        <?php if (
                                            $orderId > 0
                                        ): ?>

                                            • Order #<?= $orderId; ?>

                                        <?php endif; ?>
                                    </p>

                                    <h3 class="complaint-title">
                                        <?= adminComplaintEscape(
                                            $complaint['title']
                                            ?? 'Keluhan Pelanggan'
                                        ); ?>
                                    </h3>

                                    <p class="complaint-meta">
                                        <?= adminComplaintEscape(
                                            $buyerName
                                        ); ?>

                                        •

                                        <?= adminComplaintEscape(
                                            $serviceName
                                        ); ?>

                                        •

                                        <?= adminComplaintEscape(
                                            $mitraName
                                        ); ?>

                                        •

                                        <?= adminComplaintEscape(
                                            adminComplaintDate(
                                                $complaint['created_at']
                                                ?? null
                                            )
                                        ); ?>
                                    </p>
                                </div>

                                <span
                                    class="
                                        complaint-status
                                        complaint-status-<?= adminComplaintEscape(
                                            $complaintStatus
                                        ); ?>
                                    "
                                >
                                    <?= adminComplaintEscape(
                                        adminComplaintStatusLabel(
                                            $complaintStatus
                                        )
                                    ); ?>
                                </span>

                            </header>

                            <div class="complaint-info-grid">

                                <div class="complaint-info">
                                    <span>
                                        Pelanggan
                                    </span>

                                    <strong>
                                        <?= adminComplaintEscape(
                                            $buyerName
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="complaint-info">
                                    <span>
                                        Email
                                    </span>

                                    <strong>
                                        <?= adminComplaintEscape(
                                            $buyerEmail
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="complaint-info">
                                    <span>
                                        Telepon
                                    </span>

                                    <strong>
                                        <?= adminComplaintEscape(
                                            $buyerPhone
                                        ); ?>
                                    </strong>
                                </div>

                                <div class="complaint-info">
                                    <span>
                                        Seller
                                    </span>

                                    <strong>
                                        <?= adminComplaintEscape(
                                            $mitraName
                                        ); ?>
                                    </strong>
                                </div>

                            </div>

                            <section class="complaint-message">
                                <strong>
                                    Isi Keluhan
                                </strong>

                                <p><?= adminComplaintEscape(
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

                                <section class="reply-list">

                                    <p class="reply-list-title">
                                        Riwayat Balasan
                                    </p>

                                    <?php foreach (
                                        $repliesByComplaint[
                                            $complaintId
                                        ]
                                        as $reply
                                    ): ?>

                                        <article class="reply-card">

                                            <header class="reply-header">
                                                <strong>
                                                    <?= adminComplaintEscape(
                                                        $reply['replier_name']
                                                        ?? 'Admin Laundry'
                                                    ); ?>
                                                </strong>

                                                <time>
                                                    <?= adminComplaintEscape(
                                                        adminComplaintDate(
                                                            $reply['created_at']
                                                            ?? null
                                                        )
                                                    ); ?>
                                                </time>
                                            </header>

                                            <p><?= adminComplaintEscape(
                                                $reply['reply']
                                                ?? ''
                                            ); ?></p>

                                        </article>

                                    <?php endforeach; ?>

                                </section>

                            <?php endif; ?>

                            <div class="complaint-action-grid">

                                <form
                                    method="post"
                                    class="complaint-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= adminComplaintEscape(
                                            $_SESSION[
                                                'admin_complaint_csrf'
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
                                        for="reply-<?= $complaintId; ?>"
                                    >
                                        Balas Keluhan
                                    </label>

                                    <textarea
                                        id="reply-<?= $complaintId; ?>"
                                        name="reply"
                                        maxlength="3000"
                                        placeholder="Tuliskan balasan admin"
                                        required
                                    ></textarea>

                                    <button
                                        type="submit"
                                        class="complaint-button"
                                    >
                                        Kirim Balasan
                                    </button>
                                </form>

                                <form
                                    method="post"
                                    class="complaint-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= adminComplaintEscape(
                                            $_SESSION[
                                                'admin_complaint_csrf'
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
                                        for="status-<?= $complaintId; ?>"
                                    >
                                        Status Keluhan
                                    </label>

                                    <select
                                        id="status-<?= $complaintId; ?>"
                                        name="status"
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
                                        class="complaint-button"
                                    >
                                        Simpan Status
                                    </button>
                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <div
                    class="admin-empty"
                    id="complaintFilterEmpty"
                    hidden
                >
                    Keluhan tidak ditemukan.
                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<script>
(function () {
    const sidebar =
        document.getElementById(
            'adminSidebar'
        );

    const overlay =
        document.getElementById(
            'adminOverlay'
        );

    const toggle =
        document.getElementById(
            'adminToggle'
        );

    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove(
                'open'
            );
        }

        if (overlay) {
            overlay.classList.remove(
                'open'
            );
        }

        document.body.style.overflow = '';

        if (toggle) {
            toggle.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    }

    if (
        sidebar
        && overlay
        && toggle
    ) {
        toggle.addEventListener(
            'click',
            function () {
                const opened =
                    sidebar.classList.toggle(
                        'open'
                    );

                overlay.classList.toggle(
                    'open',
                    opened
                );

                document.body.style.overflow =
                    opened
                        ? 'hidden'
                        : '';

                toggle.setAttribute(
                    'aria-expanded',
                    opened ? 'true' : 'false'
                );
            }
        );

        overlay.addEventListener(
            'click',
            closeSidebar
        );

        window.addEventListener(
            'resize',
            function () {
                if (
                    window.innerWidth
                    > 1024
                ) {
                    closeSidebar();
                }
            }
        );
    }

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
            'adminClock'
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

    setInterval(
        updateClock,
        30000
    );

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
                '.complaint-card'
            )
        );

    const filterEmpty =
        document.getElementById(
            'complaintFilterEmpty'
        );

    function filterComplaints() {
        const keyword =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const selectedStatus =
            statusFilter
                ? statusFilter.value
                : 'all';

        let visibleCount = 0;

        complaintCards.forEach(
            function (card) {
                const searchData =
                    (
                        card.dataset.search
                        || ''
                    ).toLowerCase();

                const status =
                    card.dataset.status
                    || '';

                const searchMatch =
                    keyword === ''
                    || searchData.includes(
                        keyword
                    );

                const statusMatch =
                    selectedStatus === 'all'
                    || status ===
                        selectedStatus;

                const visible =
                    searchMatch
                    && statusMatch;

                card.classList.toggle(
                    'hidden',
                    !visible
                );

                if (visible) {
                    visibleCount++;
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