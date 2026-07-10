<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = (int) $_GET['id'];
$admin_id = $_SESSION['user']['id'];

$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM laundry_orders
    WHERE id='$order_id'
"));

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

if (isset($_GET['payment']) && $_GET['payment'] == 'paid') {

    mysqli_query($conn, "
        UPDATE laundry_payments
        SET 
            payment_status='paid',
            paid_at=NOW()
        WHERE order_id='$order_id'
    ");

    mysqli_query($conn, "
        INSERT INTO notifications(user_id, title, message)
        VALUES(
            '{$order['user_id']}',
            'Pembayaran Dikonfirmasi',
            'Pembayaran untuk pesanan laundry #$order_id telah ditandai lunas.'
        )
    ");

    header("Location: orders.php");
    exit;
}

if (!isset($_GET['status'])) {
    header("Location: orders.php");
    exit;
}

$new_status = $_GET['status'];
$allowed = ['diproses', 'dicuci', 'selesai', 'diambil'];

if (!in_array($new_status, $allowed)) {
    die("Status tidak valid.");
}

$old_status = $order['status'];

mysqli_query($conn, "
    UPDATE laundry_orders
    SET 
        status='$new_status',
        staff_id='$admin_id'
    WHERE id='$order_id'
");

mysqli_query($conn, "
    INSERT INTO laundry_order_status_logs(
        order_id,
        user_id,
        old_status,
        new_status,
        note
    )
    VALUES(
        '$order_id',
        '$admin_id',
        '$old_status',
        '$new_status',
        'Status diperbarui oleh admin.'
    )
");

if ($new_status == 'diambil') {

    $existingPickup = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT id
        FROM laundry_pickups
        WHERE order_id='$order_id'
        LIMIT 1
    "));

    if (!$existingPickup) {
        mysqli_query($conn, "
            INSERT INTO laundry_pickups(
                order_id,
                user_id,
                pickup_date,
                received_by,
                pickup_note
            )
            VALUES(
                '$order_id',
                '{$order['user_id']}',
                NOW(),
                '{$order['customer_name']}',
                'Pesanan sudah diambil pelanggan.'
            )
        ");
    }
}

mysqli_query($conn, "
    INSERT INTO notifications(user_id, title, message)
    VALUES(
        '{$order['user_id']}',
        'Status Laundry Diperbarui',
        'Status pesanan laundry #$order_id berubah menjadi $new_status.'
    )
");

header("Location: orders.php");
exit;