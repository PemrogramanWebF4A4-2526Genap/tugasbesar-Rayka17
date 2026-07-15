<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

$checks = [];

function addCheck(&$checks, $name, $status, $message)
{
    $checks[] = [
        'name' => $name,
        'status' => $status,
        'message' => $message
    ];
}

function tableExists($conn, $table)
{
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}

function columnExists($conn, $table, $column)
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    if (!tableExists($conn, $table)) {
        return false;
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function fileExistsCheck($path)
{
    return file_exists($path);
}


// ===============================
// CEK DATABASE CONNECTION
// ===============================
if ($conn) {
    addCheck($checks, "Koneksi Database", true, "Database berhasil terhubung.");
} else {
    addCheck($checks, "Koneksi Database", false, "Database gagal terhubung.");
}


// ===============================
// CEK TABEL UTAMA
// ===============================
$requiredTables = [
    'users',
    'laundry_mitras',
    'laundry_services',
    'laundry_orders',
    'laundry_payments',
    'laundry_staff_tasks',
    'laundry_order_status_logs',
    'staff',
    'notifications',
    'complaints',
    'complaint_replies'
];

foreach ($requiredTables as $table) {
    addCheck(
        $checks,
        "Tabel: $table",
        tableExists($conn, $table),
        tableExists($conn, $table) ? "Tabel tersedia." : "Tabel belum ada."
    );
}


// ===============================
// CEK KOLOM PENTING
// ===============================
$requiredColumns = [
    ['users', 'role'],
    ['users', 'mitra_id'],
    ['users', 'status'],

    ['laundry_mitras', 'mitra_name'],
    ['laundry_mitras', 'pickup_fee'],
    ['laundry_mitras', 'delivery_fee'],

    ['laundry_services', 'mitra_id'],
    ['laundry_services', 'service_name'],
    ['laundry_services', 'price_per_kg'],
    ['laundry_services', 'status'],

    ['laundry_orders', 'mitra_id'],
    ['laundry_orders', 'staff_id'],
    ['laundry_orders', 'delivery_option'],
    ['laundry_orders', 'payment_method'],
    ['laundry_orders', 'payment_status'],
    ['laundry_orders', 'picked_up_at'],
    ['laundry_orders', 'delivered_at'],

    ['laundry_staff_tasks', 'order_id'],
    ['laundry_staff_tasks', 'staff_id'],
    ['laundry_staff_tasks', 'task_type'],
    ['laundry_staff_tasks', 'task_status'],

    ['staff', 'mitra_id'],
    ['staff', 'user_id'],
    ['staff', 'fullname'],

    ['complaints', 'buyer_id'],
    ['complaints', 'order_id'],
    ['complaints', 'status'],

    ['complaint_replies', 'complaint_id'],
    ['complaint_replies', 'replier_id'],
    ['complaint_replies', 'reply'],
];

foreach ($requiredColumns as $item) {
    [$table, $column] = $item;

    addCheck(
        $checks,
        "Kolom: $table.$column",
        columnExists($conn, $table, $column),
        columnExists($conn, $table, $column) ? "Kolom tersedia." : "Kolom belum ada."
    );
}


// ===============================
// CEK ROLE USER
// ===============================
$roles = ['admin', 'mitra', 'petugas', 'buyer', 'customer_service'];

foreach ($roles as $role) {
    $safeRole = mysqli_real_escape_string($conn, $role);

    $result = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM users
        WHERE role='$safeRole'
    ");

    $total = 0;

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total = (int) ($row['total'] ?? 0);
    }

    addCheck(
        $checks,
        "Role: $role",
        $total > 0,
        $total > 0 ? "Ada $total akun dengan role $role." : "Belum ada akun role $role."
    );
}


// ===============================
// CEK FILE PENTING
// ===============================
$base = realpath(__DIR__ . "/../../../");

$requiredFiles = [
    'src/views/public/login.php',
    'src/views/public/logout.php',
    'src/views/public/home.php',

    'src/views/admin/dashboard.php',
    'src/views/admin/partners.php',
    'src/views/admin/staff.php',
    'src/views/admin/orders.php',
    'src/views/admin/services.php',

    'src/views/seller/dashboard.php',
    'src/views/seller/orders.php',
    'src/views/seller/services.php',
    'src/views/seller/staff.php',
    'src/views/seller/petugas-dashboard.php',
    'src/views/seller/petugas-orders.php',
    'src/views/seller/petugas-tasks.php',
    'src/views/seller/petugas-task.php',

    'src/views/buyer/create-order.php',
    'src/views/buyer/orders.php',
    'src/views/buyer/complaints.php',

    'src/views/customer_service/dashboard.php',
    'src/views/customer_service/complaints.php',

    'src/views/layouts/admin-sidebar.php',
    'src/views/layouts/seller-sidebar.php',
    'src/views/layouts/buyer-navbar.php',
    'src/views/layouts/customer-service-sidebar.php',
    'src/views/layouts/customer-service-topbar.php',
];

foreach ($requiredFiles as $file) {
    $fullPath = $base . "/" . $file;

    addCheck(
        $checks,
        "File: $file",
        fileExistsCheck($fullPath),
        fileExistsCheck($fullPath) ? "File tersedia." : "File belum ada."
    );
}


// ===============================
// HITUNG HASIL
// ===============================
$totalCheck = count($checks);
$totalSuccess = count(array_filter($checks, fn($c) => $c['status']));
$totalFailed = $totalCheck - $totalSuccess;

?>

<!DOCTYPE html>
<html>
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
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>System Check - Laundry UMKM</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<section style="padding:34px 7%;">

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px;">
        <div>
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                Laundry UMKM
            </p>

            <h1 class="page-title">
                System Check
            </h1>

            <p class="page-subtitle">
                Pemeriksaan tabel, kolom, role, dan file utama sistem.
            </p>
        </div>

        <a href="home.php" class="modern-btn-outline">
            Kembali Home
        </a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
        <div class="modern-card" style="padding:20px;">
            <p style="color:#64748b;font-weight:800;">Total Check</p>
            <h2 style="font-size:30px;font-weight:800;color:#0369a1;margin-top:6px;">
                <?= $totalCheck; ?>
            </h2>
        </div>

        <div class="modern-card" style="padding:20px;">
            <p style="color:#64748b;font-weight:800;">Berhasil</p>
            <h2 style="font-size:30px;font-weight:800;color:#16a34a;margin-top:6px;">
                <?= $totalSuccess; ?>
            </h2>
        </div>

        <div class="modern-card" style="padding:20px;">
            <p style="color:#64748b;font-weight:800;">Perlu Dicek</p>
            <h2 style="font-size:30px;font-weight:800;color:#dc2626;margin-top:6px;">
                <?= $totalFailed; ?>
            </h2>
        </div>
    </div>

    <div class="modern-card" style="padding:22px;">

        <h2 style="font-size:23px;font-weight:900;color:#0f172a;margin-bottom:18px;">
            Detail Pemeriksaan
        </h2>

        <div style="display:flex;flex-direction:column;gap:12px;">

            <?php foreach ($checks as $check) : ?>

                <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;border:1px solid #d8f1ff;background:#f8fdff;border-radius:18px;padding:15px;">

                    <div>
                        <h3 style="font-size:16px;font-weight:900;color:#0f172a;margin:0;">
                            <?= htmlspecialchars($check['name']); ?>
                        </h3>

                        <p style="color:#64748b;margin-top:5px;font-size:13px;">
                            <?= htmlspecialchars($check['message']); ?>
                        </p>
                    </div>

                    <?php if ($check['status']) : ?>
                        <span class="status-pill" style="background:#dcfce7;color:#166534;">
                            OK
                        </span>
                    <?php else : ?>
                        <span class="status-pill" style="background:#fee2e2;color:#b91c1c;">
                            CEK
                        </span>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<style>
@media (max-width: 900px) {
    section div[style*="grid-template-columns:repeat(3,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>