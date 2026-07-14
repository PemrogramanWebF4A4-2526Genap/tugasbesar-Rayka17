<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| SESSION PELANGGAN
|--------------------------------------------------------------------------
*/

$sessionUser = [];

if (
    isset($_SESSION['user']) &&
    is_array($_SESSION['user'])
) {
    $sessionUser = $_SESSION['user'];
} elseif (
    isset($_SESSION['auth_user']) &&
    is_array($_SESSION['auth_user'])
) {
    $sessionUser = $_SESSION['auth_user'];
}

$userId =
    $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? $sessionUser['id']
    ?? null;

$userRole = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? $sessionUser['role']
            ?? ''
        )
    )
);

$roleAliases = [
    'pelanggan' => 'buyer',
    'customer' => 'buyer'
];

$userRole =
    $roleAliases[$userRole]
    ?? $userRole;

if (
    empty($userId) ||
    $userRole !== 'buyer'
) {
    header(
        'Location: ../public/login.php'
    );

    exit;
}

$userId = (int) $userId;

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$allowedFilters = [
    'semua',
    'diproses',
    'dicuci',
    'selesai',
    'diambil'
];

$selectedFilter = strtolower(
    trim(
        (string) (
            $_GET['status']
            ?? 'semua'
        )
    )
);

if (
    !in_array(
        $selectedFilter,
        $allowedFilters,
        true
    )
) {
    $selectedFilter = 'semua';
}

/*
|--------------------------------------------------------------------------
| AMBIL PESANAN
|--------------------------------------------------------------------------
*/

$orders = [];

$sql = "
    SELECT
        laundry_orders.*,
        laundry_services.service_name,
        laundry_services.unit,
        laundry_services.estimated_time,
        laundry_mitras.mitra_name,
        laundry_mitras.phone AS mitra_phone

    FROM laundry_orders

    LEFT JOIN laundry_services
        ON laundry_services.id =
           laundry_orders.service_id

    LEFT JOIN laundry_mitras
        ON laundry_mitras.id =
           laundry_orders.mitra_id

    WHERE laundry_orders.user_id = ?
";

$parameterTypes = 'i';

$parameters = [
    $userId
];

if ($selectedFilter !== 'semua') {
    $sql .= "
        AND laundry_orders.status = ?
    ";

    $parameterTypes .= 's';

    $parameters[] = $selectedFilter;
}

$sql .= "
    ORDER BY laundry_orders.id DESC
";

$orderStatement = mysqli_prepare(
    $conn,
    $sql
);

if ($orderStatement) {
    if (count($parameters) === 1) {
        mysqli_stmt_bind_param(
            $orderStatement,
            $parameterTypes,
            $parameters[0]
        );
    } else {
        mysqli_stmt_bind_param(
            $orderStatement,
            $parameterTypes,
            $parameters[0],
            $parameters[1]
        );
    }

    mysqli_stmt_execute(
        $orderStatement
    );

    $orderResult = mysqli_stmt_get_result(
        $orderStatement
    );

    if ($orderResult) {
        while (
            $order = mysqli_fetch_assoc(
                $orderResult
            )
        ) {
            $orders[] = $order;
        }
    }

    mysqli_stmt_close(
        $orderStatement
    );
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function buyerOrderEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function buyerOrderRupiah($value): string
{
    return 'Rp ' . number_format(
        (float) $value,
        0,
        ',',
        '.'
    );
}

function buyerOrderStatusLabel(
    string $status
): string {
    $labels = [
        'pending' => 'Menunggu',
        'diproses' => 'Diproses',
        'dicuci' => 'Dicuci',
        'selesai' => 'Selesai',
        'diambil' => 'Diambil',
        'dibatalkan' => 'Dibatalkan',
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

function buyerOrderPaymentLabel(
    string $status
): string {
    $labels = [
        'unpaid' => 'Belum Bayar',
        'waiting_confirmation' =>
            'Menunggu Konfirmasi',
        'paid' => 'Lunas',
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

function buyerOrderDeliveryLabel(
    string $option
): string {
    $labels = [
        'self_service' =>
            'Antar dan ambil sendiri',

        'pickup_only' =>
            'Dijemput saja',

        'delivery_only' =>
            'Diantar saja',

        'pickup_delivery' =>
            'Antar jemput'
    ];

    return $labels[$option]
        ?? '-';
}

function buyerOrderPaymentMethod(
    string $method
): string {
    $labels = [
        'cod' => 'COD',
        'transfer' => 'Transfer'
    ];

    return $labels[$method]
        ?? strtoupper($method);
}

function buyerOrderDate($value): string
{
    if (
        empty($value) ||
        $value === '0000-00-00 00:00:00'
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

/*
|--------------------------------------------------------------------------
| URL
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

$createOrderUrl =
    $baseUrl
    . '/src/views/buyer/create-order.php';

$complaintUrl =
    $baseUrl
    . '/src/views/buyer/complaints.php';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    <title>Pesanan Saya</title>

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
        :root {
            --orders-primary: #0284c7;
            --orders-secondary: #0ea5e9;
            --orders-dark-blue: #075985;
            --orders-dark: #07152d;
            --orders-muted: #64748b;
            --orders-border: #bae6fd;
            --orders-soft: #f8fdff;
            --orders-white: #ffffff;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            width: 100%;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            color: var(--orders-dark);
            font-family:
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
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
            font: inherit;
        }

        .orders-page {
            min-height: calc(100vh - 72px);
            padding: 34px 7% 60px;
        }

        .orders-container {
            width: min(1160px, 100%);
            margin: 0 auto;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADING
        |--------------------------------------------------------------------------
        */

        .orders-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .orders-eyebrow {
            margin: 0 0 6px;
            color: var(--orders-primary);
            font-size: 14px;
            font-weight: 800;
        }

        .orders-title {
            margin: 0;
            color: var(--orders-dark);
            font-size: 36px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.035em;
        }

        .orders-description {
            margin: 9px 0 0;
            color: var(--orders-muted);
            font-size: 15px;
            font-weight: 400;
            line-height: 1.6;
        }

        .orders-heading-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .orders-button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 11px 20px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            white-space: nowrap;
        }

        .orders-button-primary {
            background:
                linear-gradient(
                    135deg,
                    var(--orders-secondary),
                    #2563eb
                );
            box-shadow:
                0 10px 25px
                rgba(2, 132, 199, 0.18);
            color: var(--orders-white);
        }

        .orders-button-secondary {
            border-color: var(--orders-border);
            background: var(--orders-white);
            color: var(--orders-dark-blue);
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .orders-alert {
            margin-bottom: 20px;
            padding: 14px 18px;
            border: 1px solid #a7f3d0;
            border-radius: 18px;
            background: #dcfce7;
            color: #166534;
            font-size: 14px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .orders-filter {
            display: flex;
            padding: 13px;
            align-items: center;
            gap: 9px;
            overflow-x: auto;
            border: 1px solid var(--orders-border);
            border-radius: 20px;
            background:
                rgba(255, 255, 255, 0.94);
            scrollbar-width: none;
        }

        .orders-filter::-webkit-scrollbar {
            display: none;
        }

        .orders-filter-link {
            display: inline-flex;
            min-width: max-content;
            min-height: 39px;
            align-items: center;
            justify-content: center;
            padding: 9px 16px;
            border: 1px solid var(--orders-border);
            border-radius: 999px;
            background: var(--orders-white);
            color: var(--orders-dark-blue);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .orders-filter-link.active {
            border-color: transparent;
            background:
                linear-gradient(
                    135deg,
                    var(--orders-secondary),
                    #2563eb
                );
            color: var(--orders-white);
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        .orders-list {
            display: grid;
            margin-top: 18px;
            gap: 15px;
        }

        .order-card {
            width: 100%;
            padding: 20px;
            border: 1px solid var(--orders-border);
            border-radius: 21px;
            background:
                rgba(255, 255, 255, 0.97);
            box-shadow:
                0 12px 32px
                rgba(2, 132, 199, 0.08);
        }

        .order-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .order-card-header > div:first-child {
            min-width: 0;
        }

        .order-number {
            margin: 0 0 6px;
            color: var(--orders-primary);
            font-size: 13px;
            font-weight: 800;
        }

        .order-service-name {
            margin: 0;
            overflow-wrap: anywhere;
            color: var(--orders-dark);
            font-size: 22px;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.025em;
        }

        .order-mitra {
            margin: 6px 0 0;
            color: var(--orders-muted);
            font-size: 13px;
            font-weight: 400;
            line-height: 1.55;
        }

        /*
        |--------------------------------------------------------------------------
        | BADGE
        |--------------------------------------------------------------------------
        */

        .order-badges {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 8px;
        }

        .order-badge {
            display: inline-flex;
            min-width: 64px;
            min-height: 64px;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.3;
            text-align: center;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-diproses,
        .status-dicuci {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-selesai {
            background: #d1fae5;
            color: #047857;
        }

        .status-diambil {
            background: #f1f5f9;
            color: #334155;
        }

        .status-dibatalkan,
        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .payment-paid {
            background: #d1fae5;
            color: #047857;
        }

        .payment-unpaid {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-waiting_confirmation {
            background: #e0f2fe;
            color: #0369a1;
        }

        .payment-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .order-summary {
            display: grid;
            margin-top: 14px;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .order-summary-item {
            min-width: 0;
            padding: 13px;
            border: 1px solid var(--orders-border);
            border-radius: 15px;
            background: #f0f9ff;
        }

        .order-summary-label {
            display: block;
            margin-bottom: 7px;
            color: #46627b;
            font-size: 12px;
            font-weight: 700;
        }

        .order-summary-value {
            display: block;
            overflow-wrap: anywhere;
            color: var(--orders-dark-blue);
            font-size: 18px;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.015em;
        }

        /*
        |--------------------------------------------------------------------------
        | DETAIL
        |--------------------------------------------------------------------------
        */

        .order-detail {
            margin-top: 11px;
            border: 1px solid var(--orders-border);
            border-radius: 15px;
            background: var(--orders-soft);
        }

        .order-detail summary {
            padding: 14px;
            cursor: pointer;
            color: var(--orders-dark-blue);
            font-size: 13px;
            font-weight: 800;
            line-height: 1.4;
            list-style-position: inside;
        }

        .order-detail-content {
            padding: 0 15px 15px;
            border-top: 1px solid #e0f2fe;
        }

        .order-detail-grid {
            display: grid;
            padding-top: 14px;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .order-detail-item {
            min-width: 0;
            padding: 13px;
            border: 1px solid #d8f1ff;
            border-radius: 13px;
            background: var(--orders-white);
        }

        .order-detail-item strong {
            display: block;
            margin-bottom: 5px;
            color: var(--orders-dark-blue);
            font-size: 12px;
            font-weight: 700;
        }

        .order-detail-item span {
            display: block;
            overflow-wrap: anywhere;
            color: var(--orders-muted);
            font-size: 13px;
            font-weight: 400;
            line-height: 1.6;
        }

        .order-detail-item-full {
            grid-column: 1 / -1;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .orders-empty {
            margin-top: 18px;
            padding: 55px 22px;
            border: 1px solid var(--orders-border);
            border-radius: 21px;
            background: var(--orders-white);
            text-align: center;
        }

        .orders-empty h2 {
            margin: 0;
            color: var(--orders-dark);
            font-size: 24px;
            font-weight: 800;
        }

        .orders-empty p {
            margin: 10px 0 21px;
            color: var(--orders-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media screen and (max-width: 800px) {
            .orders-page {
                padding:
                    24px
                    18px
                    50px;
            }

            .orders-heading {
                flex-direction: column;
            }

            .orders-heading-actions {
                width: 100%;
            }

            .orders-heading-actions .orders-button {
                flex: 1 1 0;
            }

            .order-summary {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media screen and (max-width: 520px) {
            .orders-page {
                padding:
                    22px
                    12px
                    40px;
            }

            .orders-title {
                font-size: 30px;
            }

            .orders-description {
                font-size: 14px;
            }

            .orders-heading-actions {
                display: grid;
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .orders-button {
                min-width: 0;
                padding-right: 10px;
                padding-left: 10px;
                font-size: 13px;
            }

            .orders-filter {
                border-radius: 16px;
            }

            .order-card {
                padding: 16px;
                border-radius: 18px;
            }

            .order-card-header {
                flex-direction: column;
            }

            .order-badges {
                width: 100%;
            }

            .order-badge {
                min-width: 0;
                min-height: 42px;
                flex: 1;
                border-radius: 12px;
            }

            .order-summary,
            .order-detail-grid {
                grid-template-columns: 1fr;
            }

            .order-detail-item-full {
                grid-column: auto;
            }

            .order-service-name {
                font-size: 20px;
            }

            .order-summary-value {
                font-size: 17px;
            }
        }
    </style>
</head>

<body class="soft-bg-pattern buyer-panel-page">

<?php

require_once __DIR__
    . '/../layouts/buyer-navbar.php';

?>

<main class="orders-page">
    <div class="orders-container">

        <section class="orders-heading">

            <div>
                <p class="orders-eyebrow">
                    Pelanggan
                </p>

                <h1 class="orders-title">
                    Pesanan Saya
                </h1>

                <p class="orders-description">
                    Pantau status laundry, pembayaran,
                    pickup, dan delivery.
                </p>
            </div>

            <div class="orders-heading-actions">

                <a
                    href="<?= buyerOrderEscape(
                        $createOrderUrl
                    ); ?>"
                    class="
                        orders-button
                        orders-button-primary
                    "
                >
                    Buat Pesanan
                </a>

                <a
                    href="<?= buyerOrderEscape(
                        $complaintUrl
                    ); ?>"
                    class="
                        orders-button
                        orders-button-secondary
                    "
                >
                    Keluhan
                </a>

            </div>

        </section>

        <?php if (
            isset($_GET['created'])
        ) : ?>

            <div class="orders-alert">
                Pesanan laundry berhasil dibuat.
            </div>

        <?php endif; ?>

        <nav
            class="orders-filter"
            aria-label="Filter pesanan"
        >

            <?php

            $filterMenus = [
                'semua' => 'Semua',
                'diproses' => 'Diproses',
                'dicuci' => 'Dicuci',
                'selesai' => 'Selesai',
                'diambil' => 'Diambil'
            ];

            ?>

            <?php foreach (
                $filterMenus
                as $filterValue => $filterLabel
            ) : ?>

                <a
                    href="orders.php?status=<?= buyerOrderEscape(
                        $filterValue
                    ); ?>"
                    class="
                        orders-filter-link
                        <?= $selectedFilter ===
                            $filterValue
                            ? 'active'
                            : ''; ?>
                    "
                >
                    <?= buyerOrderEscape(
                        $filterLabel
                    ); ?>
                </a>

            <?php endforeach; ?>

        </nav>

        <?php if (empty($orders)) : ?>

            <section class="orders-empty">

                <h2>
                    Belum ada pesanan
                </h2>

                <p>
                    Pesanan laundry yang dibuat akan
                    tampil dan dapat dipantau di sini.
                </p>

                <a
                    href="<?= buyerOrderEscape(
                        $createOrderUrl
                    ); ?>"
                    class="
                        orders-button
                        orders-button-primary
                    "
                >
                    Buat Pesanan Laundry
                </a>

            </section>

        <?php else : ?>

            <section class="orders-list">

                <?php foreach (
                    $orders as $order
                ) : ?>

                    <?php

                    $orderStatus = strtolower(
                        (string) (
                            $order['status']
                            ?? 'pending'
                        )
                    );

                    $paymentStatus = strtolower(
                        (string) (
                            $order['payment_status']
                            ?? 'unpaid'
                        )
                    );

                    $serviceName =
                        $order['service_name']
                        ?? 'Layanan Laundry';

                    $mitraName =
                        $order['mitra_name']
                        ?? 'Mitra Laundry';

                    $mitraPhone =
                        $order['mitra_phone']
                        ?? '-';

                    $weight = (float) (
                        $order['weight']
                        ?? 0
                    );

                    $unit =
                        $order['unit']
                        ?? 'kg';

                    $unitPrice =
                        $order['price_per_kg']
                        ?? 0;

                    $deliveryCost =
                        $order['delivery_total']
                        ?? $order['delivery_fee']
                        ?? 0;

                    $totalPrice =
                        $order['total_price']
                        ?? 0;

                    $deliveryOption =
                        $order['delivery_option']
                        ?? 'self_service';

                    $paymentMethod =
                        $order['payment_method']
                        ?? 'cod';

                    ?>

                    <article class="order-card">

                        <header class="order-card-header">

                            <div>
                                <p class="order-number">
                                    Order #<?= (int) (
                                        $order['id']
                                        ?? 0
                                    ); ?>
                                </p>

                                <h2 class="order-service-name">
                                    <?= buyerOrderEscape(
                                        $serviceName
                                    ); ?>
                                </h2>

                                <p class="order-mitra">
                                    <?= buyerOrderEscape(
                                        $mitraName
                                    ); ?>

                                    •

                                    <?= buyerOrderEscape(
                                        $mitraPhone
                                    ); ?>
                                </p>
                            </div>

                            <div class="order-badges">

                                <span
                                    class="
                                        order-badge
                                        status-<?= buyerOrderEscape(
                                            $orderStatus
                                        ); ?>
                                    "
                                >
                                    <?= buyerOrderEscape(
                                        buyerOrderStatusLabel(
                                            $orderStatus
                                        )
                                    ); ?>
                                </span>

                                <span
                                    class="
                                        order-badge
                                        payment-<?= buyerOrderEscape(
                                            $paymentStatus
                                        ); ?>
                                    "
                                >
                                    <?= buyerOrderEscape(
                                        buyerOrderPaymentLabel(
                                            $paymentStatus
                                        )
                                    ); ?>
                                </span>

                            </div>

                        </header>

                        <div class="order-summary">

                            <div class="order-summary-item">
                                <span class="order-summary-label">
                                    Berat / Jumlah
                                </span>

                                <span class="order-summary-value">
                                    <?= number_format(
                                        $weight,
                                        2,
                                        ',',
                                        '.'
                                    ); ?>

                                    <?= buyerOrderEscape(
                                        $unit
                                    ); ?>
                                </span>
                            </div>

                            <div class="order-summary-item">
                                <span class="order-summary-label">
                                    Harga Satuan
                                </span>

                                <span class="order-summary-value">
                                    <?= buyerOrderEscape(
                                        buyerOrderRupiah(
                                            $unitPrice
                                        )
                                    ); ?>
                                </span>
                            </div>

                            <div class="order-summary-item">
                                <span class="order-summary-label">
                                    Delivery
                                </span>

                                <span class="order-summary-value">
                                    <?= buyerOrderEscape(
                                        buyerOrderRupiah(
                                            $deliveryCost
                                        )
                                    ); ?>
                                </span>
                            </div>

                            <div class="order-summary-item">
                                <span class="order-summary-label">
                                    Total
                                </span>

                                <span class="order-summary-value">
                                    <?= buyerOrderEscape(
                                        buyerOrderRupiah(
                                            $totalPrice
                                        )
                                    ); ?>
                                </span>
                            </div>

                        </div>

                        <details class="order-detail">

                            <summary>
                                Detail Pesanan
                            </summary>

                            <div class="order-detail-content">

                                <div class="order-detail-grid">

                                    <div class="order-detail-item">
                                        <strong>
                                            Nama Pelanggan
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                $order[
                                                    'customer_name'
                                                ]
                                                ?? '-'
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Nomor Telepon
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                $order['phone']
                                                ?? '-'
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Seller Laundry
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                $mitraName
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Estimasi Layanan
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                $order[
                                                    'estimated_time'
                                                ]
                                                ?? '-'
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Opsi Delivery
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderDeliveryLabel(
                                                    $deliveryOption
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Metode Pembayaran
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderPaymentMethod(
                                                    $paymentMethod
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div
                                        class="
                                            order-detail-item
                                            order-detail-item-full
                                        "
                                    >
                                        <strong>
                                            Alamat Utama
                                        </strong>

                                        <span>
                                            <?= nl2br(
                                                buyerOrderEscape(
                                                    $order['address']
                                                    ?? '-'
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Alamat Pickup
                                        </strong>

                                        <span>
                                            <?= nl2br(
                                                buyerOrderEscape(
                                                    $order[
                                                        'pickup_address'
                                                    ]
                                                    ?? '-'
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Alamat Pengantaran
                                        </strong>

                                        <span>
                                            <?= nl2br(
                                                buyerOrderEscape(
                                                    $order[
                                                        'delivery_address'
                                                    ]
                                                    ?? '-'
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div
                                        class="
                                            order-detail-item
                                            order-detail-item-full
                                        "
                                    >
                                        <strong>
                                            Catatan
                                        </strong>

                                        <span>
                                            <?= nl2br(
                                                buyerOrderEscape(
                                                    $order['notes']
                                                    ?? '-'
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Status Laundry
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderStatusLabel(
                                                    $orderStatus
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Status Pembayaran
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderPaymentLabel(
                                                    $paymentStatus
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Biaya Pickup
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderRupiah(
                                                    $order[
                                                        'pickup_fee'
                                                    ]
                                                    ?? 0
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Biaya Pengantaran
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderRupiah(
                                                    $order[
                                                        'delivery_fee'
                                                    ]
                                                    ?? 0
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Tanggal Pesanan
                                        </strong>

                                        <span>
                                            <?= buyerOrderEscape(
                                                buyerOrderDate(
                                                    $order[
                                                        'created_at'
                                                    ]
                                                    ?? null
                                                )
                                            ); ?>
                                        </span>
                                    </div>

                                    <div class="order-detail-item">
                                        <strong>
                                            Nomor Pesanan
                                        </strong>

                                        <span>
                                            #<?= (int) (
                                                $order['id']
                                                ?? 0
                                            ); ?>
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </details>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </div>
</main>

<script src="../../assets/js/modern.js"></script>

</body>
</html>