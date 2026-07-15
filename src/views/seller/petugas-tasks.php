<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

/*
|--------------------------------------------------------------------------
| CEK LOGIN PETUGAS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user'])
    || !is_array($_SESSION['user'])
    || ($_SESSION['user']['role'] ?? '') !== 'petugas'
) {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) ($user['id'] ?? 0);

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('ptEscape')) {
    function ptEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('ptMoney')) {
    function ptMoney($value): string
    {
        return 'Rp '
            . number_format(
                (float) $value,
                0,
                ',',
                '.'
            );
    }
}

if (!function_exists('ptDate')) {
    function ptDate($value): string
    {
        if (
            empty($value)
            || $value === '0000-00-00 00:00:00'
        ) {
            return '-';
        }

        $timestamp = strtotime(
            (string) $value
        );

        if (!$timestamp) {
            return '-';
        }

        return date(
            'd/m/Y H:i',
            $timestamp
        );
    }
}

if (!function_exists('ptStatusLabel')) {
    function ptStatusLabel(string $status): string
    {
        $labels = [
            'waiting' => 'Menunggu',
            'assigned' => 'Ditugaskan',
            'on_process' => 'Diproses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $labels[$status]
            ?? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $status
                )
            );
    }
}

if (!function_exists('ptTaskTypeLabel')) {
    function ptTaskTypeLabel(string $type): string
    {
        $labels = [
            'pickup' => 'Pickup / Jemput',
            'delivery' => 'Delivery / Antar'
        ];

        return $labels[$type]
            ?? ucfirst($type);
    }
}

/*
|--------------------------------------------------------------------------
| DATA STAFF
|--------------------------------------------------------------------------
*/

$staff = null;

$staffQuery = mysqli_query(
    $conn,
    "
        SELECT
            staff.*,
            laundry_mitras.mitra_name,
            laundry_mitras.phone AS mitra_phone,
            laundry_mitras.address AS mitra_address
        FROM staff

        JOIN laundry_mitras
            ON staff.mitra_id = laundry_mitras.id

        WHERE staff.user_id = '$user_id'

        LIMIT 1
    "
);

if ($staffQuery) {
    $staff = mysqli_fetch_assoc(
        $staffQuery
    );
}

$staff_id = (int) (
    $staff['id']
    ?? 0
);

$mitra_id = (int) (
    $staff['mitra_id']
    ?? 0
);

/*
|--------------------------------------------------------------------------
| IDENTITAS STAFF
|--------------------------------------------------------------------------
|
| Sebagian data lama mungkin menyimpan staff_id dengan users.id.
| Sebagian data baru memakai staff.id.
| Maka dua ID didukung agar tugas tetap terbaca.
|
|--------------------------------------------------------------------------
*/

$staffIdentities = [];

if ($staff_id > 0) {
    $staffIdentities[] = $staff_id;
}

if ($user_id > 0) {
    $staffIdentities[] = $user_id;
}

$staffIdentities = array_values(
    array_unique(
        array_map(
            'intval',
            $staffIdentities
        )
    )
);

$staffIdentitySql = !empty($staffIdentities)
    ? implode(',', $staffIdentities)
    : '0';

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['petugas_tasks_csrf']
    )
) {
    $_SESSION['petugas_tasks_csrf'] =
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
    $_SESSION['petugas_tasks_success']
    ?? '';

$errorMessage =
    $_SESSION['petugas_tasks_error']
    ?? '';

unset(
    $_SESSION['petugas_tasks_success'],
    $_SESSION['petugas_tasks_error']
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
        $_SESSION['petugas_tasks_csrf']
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
        $_SESSION['petugas_tasks_error'] =
            'Sesi formulir tidak valid.';

        header(
            "Location: petugas-tasks.php"
        );

        exit;
    }

    if (!$staff || $mitra_id < 1) {
        $_SESSION['petugas_tasks_error'] =
            'Akun petugas belum terhubung dengan seller.';

        header(
            "Location: petugas-tasks.php"
        );

        exit;
    }

    $action = trim(
        (string) (
            $_POST['action']
            ?? ''
        )
    );

    $task_id = (int) (
        $_POST['task_id']
        ?? 0
    );

    if ($task_id < 1) {
        $_SESSION['petugas_tasks_error'] =
            'Data tugas tidak valid.';

        header(
            "Location: petugas-tasks.php"
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL TUGAS
    |--------------------------------------------------------------------------
    */

    if ($action === 'claim') {
        $claim_staff_id =
            $staff_id > 0
                ? $staff_id
                : $user_id;

        $claimQuery = mysqli_query(
            $conn,
            "
                UPDATE laundry_staff_tasks AS task

                JOIN laundry_orders AS orders
                    ON task.order_id = orders.id

                SET
                    task.staff_id = '$claim_staff_id',
                    task.task_status = 'assigned',
                    task.updated_at = NOW()

                WHERE task.id = '$task_id'

                AND orders.mitra_id = '$mitra_id'

                AND (
                    task.staff_id IS NULL
                    OR task.staff_id = 0
                )

                AND task.task_status = 'waiting'
            "
        );

        if (
            $claimQuery
            && mysqli_affected_rows($conn) > 0
        ) {
            $_SESSION['petugas_tasks_success'] =
                'Tugas berhasil diambil.';
        } else {
            $_SESSION['petugas_tasks_error'] =
                'Tugas tidak tersedia atau sudah diambil petugas lain.';
        }

        header(
            "Location: petugas-tasks.php"
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_status') {
        $task_status = strtolower(
            trim(
                (string) (
                    $_POST['task_status']
                    ?? ''
                )
            )
        );

        $note = mysqli_real_escape_string(
            $conn,
            trim(
                (string) (
                    $_POST['note']
                    ?? ''
                )
            )
        );

        $allowedStatuses = [
            'assigned',
            'on_process',
            'completed',
            'cancelled'
        ];

        if (
            !in_array(
                $task_status,
                $allowedStatuses,
                true
            )
        ) {
            $_SESSION['petugas_tasks_error'] =
                'Status tugas tidak valid.';

            header(
                "Location: petugas-tasks.php"
            );

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | CEK KEPEMILIKAN TUGAS
        |--------------------------------------------------------------------------
        */

        $taskCheck = mysqli_query(
            $conn,
            "
                SELECT
                    task.id

                FROM laundry_staff_tasks AS task

                JOIN laundry_orders AS orders
                    ON task.order_id = orders.id

                WHERE task.id = '$task_id'

                AND orders.mitra_id = '$mitra_id'

                AND task.staff_id IN (
                    $staffIdentitySql
                )

                LIMIT 1
            "
        );

        if (
            !$taskCheck
            || mysqli_num_rows($taskCheck) < 1
        ) {
            $_SESSION['petugas_tasks_error'] =
                'Kamu tidak memiliki akses ke tugas tersebut.';

            header(
                "Location: petugas-tasks.php"
            );

            exit;
        }

        if ($task_status === 'completed') {
            $updateQuery = mysqli_query(
                $conn,
                "
                    UPDATE laundry_staff_tasks

                    SET
                        task_status = '$task_status',
                        note = '$note',
                        completed_at = NOW(),
                        updated_at = NOW()

                    WHERE id = '$task_id'

                    AND staff_id IN (
                        $staffIdentitySql
                    )
                "
            );
        } else {
            $updateQuery = mysqli_query(
                $conn,
                "
                    UPDATE laundry_staff_tasks

                    SET
                        task_status = '$task_status',
                        note = '$note',
                        completed_at = NULL,
                        updated_at = NOW()

                    WHERE id = '$task_id'

                    AND staff_id IN (
                        $staffIdentitySql
                    )
                "
            );
        }

        if ($updateQuery) {
            $_SESSION['petugas_tasks_success'] =
                'Status tugas berhasil diperbarui.';
        } else {
            $_SESSION['petugas_tasks_error'] =
                'Status tugas gagal diperbarui.';
        }

        header(
            "Location: petugas-tasks.php"
        );

        exit;
    }

    $_SESSION['petugas_tasks_error'] =
        'Aksi tidak dikenali.';

    header(
        "Location: petugas-tasks.php"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| RINGKASAN DATA
|--------------------------------------------------------------------------
*/

$totalOrders = 0;
$waitingTasks = 0;
$myTasks = 0;
$completedTasks = 0;
$onProcessTasks = 0;

if ($mitra_id > 0) {
    $totalOrdersQuery = mysqli_query(
        $conn,
        "
            SELECT COUNT(*) AS total

            FROM laundry_orders

            WHERE mitra_id = '$mitra_id'
        "
    );

    if ($totalOrdersQuery) {
        $totalOrdersRow =
            mysqli_fetch_assoc(
                $totalOrdersQuery
            );

        $totalOrders = (int) (
            $totalOrdersRow['total']
            ?? 0
        );
    }

    $waitingTasksQuery = mysqli_query(
        $conn,
        "
            SELECT COUNT(*) AS total

            FROM laundry_staff_tasks AS task

            JOIN laundry_orders AS orders
                ON task.order_id = orders.id

            WHERE orders.mitra_id = '$mitra_id'

            AND task.task_status = 'waiting'

            AND (
                task.staff_id IS NULL
                OR task.staff_id = 0
            )
        "
    );

    if ($waitingTasksQuery) {
        $waitingTasksRow =
            mysqli_fetch_assoc(
                $waitingTasksQuery
            );

        $waitingTasks = (int) (
            $waitingTasksRow['total']
            ?? 0
        );
    }

    $myTasksQuery = mysqli_query(
        $conn,
        "
            SELECT COUNT(*) AS total

            FROM laundry_staff_tasks AS task

            JOIN laundry_orders AS orders
                ON task.order_id = orders.id

            WHERE orders.mitra_id = '$mitra_id'

            AND task.staff_id IN (
                $staffIdentitySql
            )
        "
    );

    if ($myTasksQuery) {
        $myTasksRow =
            mysqli_fetch_assoc(
                $myTasksQuery
            );

        $myTasks = (int) (
            $myTasksRow['total']
            ?? 0
        );
    }

    $onProcessTasksQuery = mysqli_query(
        $conn,
        "
            SELECT COUNT(*) AS total

            FROM laundry_staff_tasks AS task

            JOIN laundry_orders AS orders
                ON task.order_id = orders.id

            WHERE orders.mitra_id = '$mitra_id'

            AND task.staff_id IN (
                $staffIdentitySql
            )

            AND task.task_status = 'on_process'
        "
    );

    if ($onProcessTasksQuery) {
        $onProcessTasksRow =
            mysqli_fetch_assoc(
                $onProcessTasksQuery
            );

        $onProcessTasks = (int) (
            $onProcessTasksRow['total']
            ?? 0
        );
    }

    $completedTasksQuery = mysqli_query(
        $conn,
        "
            SELECT COUNT(*) AS total

            FROM laundry_staff_tasks AS task

            JOIN laundry_orders AS orders
                ON task.order_id = orders.id

            WHERE orders.mitra_id = '$mitra_id'

            AND task.staff_id IN (
                $staffIdentitySql
            )

            AND task.task_status = 'completed'
        "
    );

    if ($completedTasksQuery) {
        $completedTasksRow =
            mysqli_fetch_assoc(
                $completedTasksQuery
            );

        $completedTasks = (int) (
            $completedTasksRow['total']
            ?? 0
        );
    }
}

/*
|--------------------------------------------------------------------------
| DAFTAR TUGAS
|--------------------------------------------------------------------------
*/

$tasks = [];
$taskQueryError = '';

if ($mitra_id > 0) {
    $tasksQuery = mysqli_query(
        $conn,
        "
            SELECT
                task.id,
                task.order_id,
                task.staff_id,
                task.task_type,
                task.task_status,
                task.address,
                task.fee,
                task.note,
                task.completed_at,
                task.created_at,
                task.updated_at,

                orders.customer_name,
                orders.phone AS customer_phone,
                orders.status AS order_status,
                orders.total_price,
                orders.pickup_address,
                orders.delivery_address,

                services.service_name,
                services.unit,

                mitras.mitra_name

            FROM laundry_staff_tasks AS task

            JOIN laundry_orders AS orders
                ON task.order_id = orders.id

            LEFT JOIN laundry_services AS services
                ON orders.service_id = services.id

            LEFT JOIN laundry_mitras AS mitras
                ON orders.mitra_id = mitras.id

            WHERE orders.mitra_id = '$mitra_id'

            AND (
                (
                    task.task_status = 'waiting'

                    AND (
                        task.staff_id IS NULL
                        OR task.staff_id = 0
                    )
                )

                OR

                task.staff_id IN (
                    $staffIdentitySql
                )
            )

            ORDER BY
                CASE task.task_status
                    WHEN 'waiting' THEN 1
                    WHEN 'assigned' THEN 2
                    WHEN 'on_process' THEN 3
                    WHEN 'completed' THEN 4
                    WHEN 'cancelled' THEN 5
                    ELSE 6
                END,

                task.id DESC
        "
    );

    if ($tasksQuery) {
        while (
            $task = mysqli_fetch_assoc(
                $tasksQuery
            )
        ) {
            $tasks[] = $task;
        }
    } else {
        $taskQueryError =
            mysqli_error($conn);
    }
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

    <title>Tugas Petugas</title>

    <link
        rel="stylesheet"
        href="../../assets/css/output.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/modern.css"
    >
    <link rel="stylesheet" href="../../assets/css/responsive.css">

    <style>
        /*
        |--------------------------------------------------------------------------
        | PAGE CONTENT
        |--------------------------------------------------------------------------
        */

        .pt-content {
            padding: 26px;
        }

        .pt-heading {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 16px;

            margin-bottom: 22px;
        }

        .pt-eyebrow {
            margin:
                0
                0
                6px;

            color: #0284c7;

            font-size: 13px;
            font-weight: 800;
        }

        .pt-title {
            margin: 0;

            color: #07152d;

            font-size: 36px;
            font-weight: 800;
            line-height: 1.1;

            letter-spacing: -1px;
        }

        .pt-subtitle {
            margin:
                9px
                0
                0;

            color: #64748b;

            font-size: 14px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .pt-alert {
            margin-bottom: 20px;

            padding:
                14px
                18px;

            border-radius: 16px;

            font-size: 13px;
            font-weight: 700;
        }

        .pt-alert-success {
            border:
                1px solid
                #a7f3d0;

            background: #dcfce7;

            color: #166534;
        }

        .pt-alert-error {
            border:
                1px solid
                #fecaca;

            background: #fee2e2;

            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .pt-summary-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    5,
                    minmax(0, 1fr)
                );

            gap: 16px;

            margin-bottom: 22px;
        }

        .pt-summary-card {
            min-width: 0;

            padding: 20px;

            border:
                1px solid
                #b6e4fa;

            border-radius: 18px;

            background: #ffffff;

            box-shadow:
                0 12px 30px
                rgba(2, 132, 199, 0.06);
        }

        .pt-summary-label {
            display: block;

            color: #64748b;

            font-size: 12px;
            font-weight: 800;
        }

        .pt-summary-value {
            display: block;

            margin-top: 9px;

            color: #0369a1;

            font-size: 29px;
            font-weight: 800;
            line-height: 1;
        }

        .pt-summary-card.waiting
        .pt-summary-value {
            color: #f59e0b;
        }

        .pt-summary-card.assigned
        .pt-summary-value {
            color: #2563eb;
        }

        .pt-summary-card.process
        .pt-summary-value {
            color: #0284c7;
        }

        .pt-summary-card.completed
        .pt-summary-value {
            color: #16a34a;
        }

        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .pt-panel {
            padding: 22px;

            border:
                1px solid
                #b6e4fa;

            border-radius: 21px;

            background: #ffffff;

            box-shadow:
                0 13px 33px
                rgba(2, 132, 199, 0.06);
        }

        .pt-panel-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 16px;

            margin-bottom: 18px;
        }

        .pt-panel-title {
            margin: 0;

            color: #0f172a;

            font-size: 23px;
            font-weight: 800;
        }

        .pt-panel-description {
            margin:
                6px
                0
                0;

            color: #64748b;

            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .pt-filter {
            display: flex;

            width:
                min(
                    430px,
                    100%
                );

            gap: 10px;
        }

        .pt-filter input,
        .pt-filter select {
            min-height: 44px;

            border:
                1px solid
                #b6e4fa;

            border-radius: 13px;

            outline: none;

            background: #f4fbff;

            color: #07152d;

            font-size: 13px;
        }

        .pt-filter input {
            width: 100%;
            min-width: 0;

            padding:
                0
                13px;
        }

        .pt-filter select {
            width: 155px;
            flex: 0 0 155px;

            padding:
                0
                10px;
        }

        .pt-filter input:focus,
        .pt-filter select:focus {
            border-color: #0ea5e9;

            box-shadow:
                0 0 0 4px
                rgba(14, 165, 233, 0.10);
        }

        /*
        |--------------------------------------------------------------------------
        | TASK LIST
        |--------------------------------------------------------------------------
        */

        .pt-task-list {
            display: grid;

            gap: 15px;
        }

        .pt-task-card {
            min-width: 0;

            padding: 19px;

            border:
                1px solid
                #d5eef9;

            border-radius: 18px;

            background: #f8fdff;
        }

        .pt-task-card.pt-hidden {
            display: none;
        }

        .pt-task-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;
        }

        .pt-task-header > div {
            min-width: 0;
        }

        .pt-task-number {
            margin:
                0
                0
                5px;

            color: #0284c7;

            font-size: 12px;
            font-weight: 800;
        }

        .pt-task-name {
            margin: 0;

            color: #0f172a;

            font-size: 20px;
            font-weight: 800;

            overflow-wrap: anywhere;
        }

        .pt-task-meta {
            margin:
                7px
                0
                0;

            color: #64748b;

            font-size: 12px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .pt-status {
            display: inline-flex;

            min-height: 38px;

            flex: 0 0 auto;

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

        .pt-status-waiting {
            background: #fef3c7;

            color: #92400e;
        }

        .pt-status-assigned {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .pt-status-on_process {
            background: #e0f2fe;

            color: #0369a1;
        }

        .pt-status-completed {
            background: #dcfce7;

            color: #166534;
        }

        .pt-status-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | INFO
        |--------------------------------------------------------------------------
        */

        .pt-info-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 11px;

            margin-top: 15px;
        }

        .pt-info-item {
            min-width: 0;

            padding: 13px;

            border:
                1px solid
                #d5eef9;

            border-radius: 14px;

            background: #ffffff;
        }

        .pt-info-item span,
        .pt-info-item strong {
            display: block;
        }

        .pt-info-item span {
            margin-bottom: 6px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;
        }

        .pt-info-item strong {
            overflow-wrap: anywhere;

            color: #0369a1;

            font-size: 13px;
            font-weight: 800;
        }

        .pt-address {
            margin-top: 11px;

            padding: 14px;

            border:
                1px solid
                #d5eef9;

            border-radius: 14px;

            background: #ffffff;

            color: #334155;

            font-size: 13px;
            line-height: 1.7;

            overflow-wrap: anywhere;
        }

        .pt-address strong {
            color: #0369a1;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .pt-form {
            display: grid;

            grid-template-columns:
                190px
                minmax(0, 1fr)
                auto;

            align-items: end;

            gap: 11px;

            margin-top: 14px;
        }

        .pt-field {
            min-width: 0;
        }

        .pt-field label {
            display: block;

            margin-bottom: 7px;

            color: #0369a1;

            font-size: 11px;
            font-weight: 800;
        }

        .pt-field input,
        .pt-field select {
            width: 100%;
            min-height: 43px;

            padding:
                0
                11px;

            border:
                1px solid
                #b6e4fa;

            border-radius: 11px;

            outline: none;

            background: #ffffff;

            color: #07152d;

            font-size: 13px;
        }

        .pt-field input:focus,
        .pt-field select:focus {
            border-color: #0ea5e9;

            box-shadow:
                0 0 0 4px
                rgba(14, 165, 233, 0.10);
        }

        .pt-button {
            display: inline-flex;

            min-height: 43px;

            cursor: pointer;

            align-items: center;
            justify-content: center;

            padding:
                10px
                18px;

            border: 0;

            border-radius: 999px;

            background:
                linear-gradient(
                    135deg,
                    #0ea5e9,
                    #2563eb
                );

            box-shadow:
                0 10px 23px
                rgba(2, 132, 199, 0.18);

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;

            white-space: nowrap;
        }

        .pt-button:hover {
            filter: brightness(0.97);
        }

        .pt-button-claim {
            margin-top: 14px;

            background: #059669;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .pt-empty {
            padding:
                50px
                20px;

            color: #64748b;

            text-align: center;
        }

        .pt-empty h3 {
            margin: 0;

            color: #0f172a;

            font-size: 20px;
            font-weight: 800;
        }

        .pt-empty p {
            margin:
                8px
                0
                0;

            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 1100px) {
            .pt-summary-grid {
                grid-template-columns:
                    repeat(
                        3,
                        minmax(0, 1fr)
                    );
            }

            .pt-info-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }

        @media screen and (max-width: 760px) {
            .pt-content {
                padding:
                    22px
                    13px
                    40px;
            }

            .pt-heading,
            .pt-panel-header,
            .pt-task-header {
                flex-direction: column;
            }

            .pt-title {
                font-size: 30px;
            }

            .pt-filter {
                width: 100%;

                flex-direction: column;
            }

            .pt-filter select {
                width: 100%;
                flex-basis: auto;
            }

            .pt-status {
                width: 100%;

                border-radius: 12px;
            }

            .pt-form {
                grid-template-columns:
                    minmax(0, 1fr);
            }

            .pt-button {
                width: 100%;
            }
        }

        @media screen and (max-width: 520px) {
            .pt-summary-grid,
            .pt-info-grid {
                grid-template-columns:
                    minmax(0, 1fr);
            }

            .pt-panel {
                padding: 16px;
            }

            .pt-task-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body class="soft-bg-pattern seller-panel-page">

<?php include "../layouts/seller-sidebar.php"; ?>

<div
    class="mobile-overlay"
    onclick="closeSidebar()"
></div>

<main class="dashboard-main">

    <?php include "../layouts/seller-topbar.php"; ?>

    <section class="pt-content">

        <div class="pt-heading">

            <div>
                <p class="pt-eyebrow">
                    Panel Petugas
                </p>

                <h1 class="pt-title">
                    Tugas Pickup dan Delivery
                </h1>

                <p class="pt-subtitle">
                    Lihat, ambil, dan perbarui status tugas laundry.
                </p>
            </div>

            <a
                href="petugas-dashboard.php"
                class="modern-btn-outline"
            >
                Kembali ke Dashboard
            </a>

        </div>

        <?php if (
            $successMessage !== ''
        ) : ?>

            <div
                class="
                    pt-alert
                    pt-alert-success
                "
            >
                <?= ptEscape(
                    $successMessage
                ); ?>
            </div>

        <?php endif; ?>

        <?php if (
            $errorMessage !== ''
        ) : ?>

            <div
                class="
                    pt-alert
                    pt-alert-error
                "
            >
                <?= ptEscape(
                    $errorMessage
                ); ?>
            </div>

        <?php endif; ?>

        <?php if (
            $taskQueryError !== ''
        ) : ?>

            <div
                class="
                    pt-alert
                    pt-alert-error
                "
            >
                Data tugas gagal dibaca:

                <?= ptEscape(
                    $taskQueryError
                ); ?>
            </div>

        <?php endif; ?>

        <?php if (!$staff) : ?>

            <div
                class="
                    modern-card
                    pt-empty
                "
            >
                <h3>
                    Akun Petugas Belum Terhubung
                </h3>

                <p>
                    Seller perlu menghubungkan akun ini ke data petugas.
                </p>
            </div>

        <?php else : ?>

            <section class="pt-summary-grid">

                <article class="pt-summary-card">
                    <span class="pt-summary-label">
                        Pesanan Seller
                    </span>

                    <strong class="pt-summary-value">
                        <?= $totalOrders; ?>
                    </strong>
                </article>

                <article
                    class="
                        pt-summary-card
                        waiting
                    "
                >
                    <span class="pt-summary-label">
                        Tugas Menunggu
                    </span>

                    <strong class="pt-summary-value">
                        <?= $waitingTasks; ?>
                    </strong>
                </article>

                <article
                    class="
                        pt-summary-card
                        assigned
                    "
                >
                    <span class="pt-summary-label">
                        Tugas Saya
                    </span>

                    <strong class="pt-summary-value">
                        <?= $myTasks; ?>
                    </strong>
                </article>

                <article
                    class="
                        pt-summary-card
                        process
                    "
                >
                    <span class="pt-summary-label">
                        Sedang Diproses
                    </span>

                    <strong class="pt-summary-value">
                        <?= $onProcessTasks; ?>
                    </strong>
                </article>

                <article
                    class="
                        pt-summary-card
                        completed
                    "
                >
                    <span class="pt-summary-label">
                        Selesai
                    </span>

                    <strong class="pt-summary-value">
                        <?= $completedTasks; ?>
                    </strong>
                </article>

            </section>

            <section class="pt-panel">

                <header class="pt-panel-header">

                    <div>
                        <h2 class="pt-panel-title">
                            Daftar Tugas
                        </h2>

                        <p class="pt-panel-description">
                            Tugas menunggu dan tugas yang telah kamu ambil.
                        </p>
                    </div>

                    <div class="pt-filter">

                        <input
                            type="search"
                            id="ptTaskSearch"
                            placeholder="Cari pelanggan atau layanan"
                            autocomplete="off"
                        >

                        <select id="ptStatusFilter">
                            <option value="all">
                                Semua Status
                            </option>

                            <option value="waiting">
                                Menunggu
                            </option>

                            <option value="assigned">
                                Ditugaskan
                            </option>

                            <option value="on_process">
                                Diproses
                            </option>

                            <option value="completed">
                                Selesai
                            </option>

                            <option value="cancelled">
                                Dibatalkan
                            </option>
                        </select>

                    </div>

                </header>

                <?php if (empty($tasks)) : ?>

                    <div class="pt-empty">
                        <h3>
                            Belum Ada Tugas
                        </h3>

                        <p>
                            Tugas pickup dan delivery akan muncul di halaman ini.
                        </p>
                    </div>

                <?php else : ?>

                    <div class="pt-task-list">

                        <?php foreach (
                            $tasks as $task
                        ) : ?>

                            <?php

                            $taskId = (int) (
                                $task['id']
                                ?? 0
                            );

                            $taskStatus = strtolower(
                                (string) (
                                    $task['task_status']
                                    ?? 'waiting'
                                )
                            );

                            $taskType = strtolower(
                                (string) (
                                    $task['task_type']
                                    ?? 'pickup'
                                )
                            );

                            $assignedStaffId = (int) (
                                $task['staff_id']
                                ?? 0
                            );

                            $isMyTask = in_array(
                                $assignedStaffId,
                                $staffIdentities,
                                true
                            );

                            $canClaim =
                                $taskStatus === 'waiting'
                                && $assignedStaffId === 0;

                            $displayAddress = trim(
                                (string) (
                                    $task['address']
                                    ?? ''
                                )
                            );

                            if ($displayAddress === '') {
                                if (
                                    $taskType === 'pickup'
                                ) {
                                    $displayAddress = trim(
                                        (string) (
                                            $task['pickup_address']
                                            ?? ''
                                        )
                                    );
                                } else {
                                    $displayAddress = trim(
                                        (string) (
                                            $task['delivery_address']
                                            ?? ''
                                        )
                                    );
                                }
                            }

                            if ($displayAddress === '') {
                                $displayAddress = '-';
                            }

                            $searchData = strtolower(
                                implode(
                                    ' ',
                                    [
                                        $task['customer_name']
                                            ?? '',
                                        $task['customer_phone']
                                            ?? '',
                                        $task['service_name']
                                            ?? '',
                                        $task['mitra_name']
                                            ?? '',
                                        $displayAddress,
                                        $taskType,
                                        $taskStatus
                                    ]
                                )
                            );

                            ?>

                            <article
                                class="pt-task-card"
                                data-status="<?= ptEscape(
                                    $taskStatus
                                ); ?>"
                                data-search="<?= ptEscape(
                                    $searchData
                                ); ?>"
                            >

                                <header class="pt-task-header">

                                    <div>
                                        <p class="pt-task-number">
                                            Tugas #<?= $taskId; ?>

                                            •

                                            Order #<?= (int) (
                                                $task['order_id']
                                                ?? 0
                                            ); ?>
                                        </p>

                                        <h3 class="pt-task-name">
                                            <?= ptEscape(
                                                ptTaskTypeLabel(
                                                    $taskType
                                                )
                                            ); ?>
                                        </h3>

                                        <p class="pt-task-meta">
                                            <?= ptEscape(
                                                $task['customer_name']
                                                ?? 'Pelanggan Laundry'
                                            ); ?>

                                            •

                                            <?= ptEscape(
                                                $task['service_name']
                                                ?? 'Layanan Laundry'
                                            ); ?>

                                            <br>

                                            <?= ptEscape(
                                                $task['mitra_name']
                                                ?? 'Mitra Laundry'
                                            ); ?>
                                        </p>
                                    </div>

                                    <span
                                        class="
                                            pt-status
                                            pt-status-<?= ptEscape(
                                                $taskStatus
                                            ); ?>
                                        "
                                    >
                                        <?= ptEscape(
                                            ptStatusLabel(
                                                $taskStatus
                                            )
                                        ); ?>
                                    </span>

                                </header>

                                <div class="pt-info-grid">

                                    <div class="pt-info-item">
                                        <span>
                                            Pelanggan
                                        </span>

                                        <strong>
                                            <?= ptEscape(
                                                $task['customer_name']
                                                ?? '-'
                                            ); ?>
                                        </strong>
                                    </div>

                                    <div class="pt-info-item">
                                        <span>
                                            Telepon
                                        </span>

                                        <strong>
                                            <?= ptEscape(
                                                $task['customer_phone']
                                                ?? '-'
                                            ); ?>
                                        </strong>
                                    </div>

                                    <div class="pt-info-item">
                                        <span>
                                            Biaya Tugas
                                        </span>

                                        <strong>
                                            <?= ptMoney(
                                                $task['fee']
                                                ?? 0
                                            ); ?>
                                        </strong>
                                    </div>

                                    <div class="pt-info-item">
                                        <span>
                                            Dibuat
                                        </span>

                                        <strong>
                                            <?= ptEscape(
                                                ptDate(
                                                    $task['created_at']
                                                    ?? null
                                                )
                                            ); ?>
                                        </strong>
                                    </div>

                                </div>

                                <div class="pt-address">
                                    <strong>
                                        Alamat:
                                    </strong>

                                    <?= nl2br(
                                        ptEscape(
                                            $displayAddress
                                        )
                                    ); ?>

                                    <?php if (
                                        trim(
                                            (string) (
                                                $task['note']
                                                ?? ''
                                            )
                                        ) !== ''
                                    ) : ?>

                                        <br><br>

                                        <strong>
                                            Catatan:
                                        </strong>

                                        <?= nl2br(
                                            ptEscape(
                                                $task['note']
                                            )
                                        ); ?>

                                    <?php endif; ?>
                                </div>

                                <?php if ($canClaim) : ?>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= ptEscape(
                                                $_SESSION[
                                                    'petugas_tasks_csrf'
                                                ]
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="claim"
                                        >

                                        <input
                                            type="hidden"
                                            name="task_id"
                                            value="<?= $taskId; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="
                                                pt-button
                                                pt-button-claim
                                            "
                                            onclick="return confirm('Ambil tugas ini?')"
                                        >
                                            Ambil Tugas
                                        </button>

                                    </form>

                                <?php elseif ($isMyTask) : ?>

                                    <form
                                        method="POST"
                                        class="pt-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= ptEscape(
                                                $_SESSION[
                                                    'petugas_tasks_csrf'
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
                                            name="task_id"
                                            value="<?= $taskId; ?>"
                                        >

                                        <div class="pt-field">
                                            <label
                                                for="status-<?= $taskId; ?>"
                                            >
                                                Status Tugas
                                            </label>

                                            <select
                                                id="status-<?= $taskId; ?>"
                                                name="task_status"
                                                required
                                            >
                                                <option
                                                    value="assigned"
                                                    <?= $taskStatus ===
                                                        'assigned'
                                                        ? 'selected'
                                                        : ''; ?>
                                                >
                                                    Ditugaskan
                                                </option>

                                                <option
                                                    value="on_process"
                                                    <?= $taskStatus ===
                                                        'on_process'
                                                        ? 'selected'
                                                        : ''; ?>
                                                >
                                                    Diproses
                                                </option>

                                                <option
                                                    value="completed"
                                                    <?= $taskStatus ===
                                                        'completed'
                                                        ? 'selected'
                                                        : ''; ?>
                                                >
                                                    Selesai
                                                </option>

                                                <option
                                                    value="cancelled"
                                                    <?= $taskStatus ===
                                                        'cancelled'
                                                        ? 'selected'
                                                        : ''; ?>
                                                >
                                                    Dibatalkan
                                                </option>
                                            </select>
                                        </div>

                                        <div class="pt-field">
                                            <label
                                                for="note-<?= $taskId; ?>"
                                            >
                                                Catatan Petugas
                                            </label>

                                            <input
                                                id="note-<?= $taskId; ?>"
                                                type="text"
                                                name="note"
                                                value="<?= ptEscape(
                                                    $task['note']
                                                    ?? ''
                                                ); ?>"
                                                placeholder="Tambahkan catatan tugas"
                                            >
                                        </div>

                                        <button
                                            type="submit"
                                            class="pt-button"
                                        >
                                            Simpan Status
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <div
                        class="pt-empty"
                        id="ptFilterEmpty"
                        hidden
                    >
                        <h3>
                            Tugas Tidak Ditemukan
                        </h3>

                        <p>
                            Coba gunakan kata pencarian atau status yang berbeda.
                        </p>
                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </section>

</main>

<script src="../../assets/js/modern.js"></script>

<script>
(function () {
    const searchInput =
        document.getElementById(
            'ptTaskSearch'
        );

    const statusFilter =
        document.getElementById(
            'ptStatusFilter'
        );

    const taskCards =
        Array.from(
            document.querySelectorAll(
                '.pt-task-card'
            )
        );

    const emptyFilter =
        document.getElementById(
            'ptFilterEmpty'
        );

    function filterTasks() {
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

        taskCards.forEach(
            function (card) {
                const searchData =
                    (
                        card.dataset.search
                        || ''
                    ).toLowerCase();

                const cardStatus =
                    card.dataset.status
                    || '';

                const searchMatch =
                    keyword === ''
                    || searchData.includes(
                        keyword
                    );

                const statusMatch =
                    selectedStatus === 'all'
                    || cardStatus ===
                        selectedStatus;

                const visible =
                    searchMatch
                    && statusMatch;

                card.classList.toggle(
                    'pt-hidden',
                    !visible
                );

                if (visible) {
                    visibleCount++;
                }
            }
        );

        if (emptyFilter) {
            emptyFilter.hidden =
                visibleCount > 0;
        }
    }

    if (searchInput) {
        searchInput.addEventListener(
            'input',
            filterTasks
        );
    }

    if (statusFilter) {
        statusFilter.addEventListener(
            'change',
            filterTasks
        );
    }
})();
</script>

</body>
</html>