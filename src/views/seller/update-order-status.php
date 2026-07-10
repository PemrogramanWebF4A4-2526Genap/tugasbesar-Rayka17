<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'seller') {
    header("Location: ../public/login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: orders.php");
    exit;
}

$order_id = (int) $_GET['id'];
$staff_id = $_SESSION['user']['id'];
$new_status = $_GET['status'];

$allowed = ['dicuci', 'selesai'];

if (!in_array($new_status, $allowed)) {
    die("Petugas hanya boleh mengubah status menjadi dicuci atau selesai.");
}

$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM laundry_orders
    WHERE id='$order_id'
"));

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

$old_status = $order['status'];

if ($old_status == 'diproses' && $new_status != 'dicuci') {
    die("Status diproses hanya bisa diubah menjadi dicuci.");
}

if ($old_status == 'dicuci' && $new_status != 'selesai') {
    die("Status dicuci hanya bisa diubah menjadi selesai.");
}

if ($old_status == 'selesai' || $old_status == 'diambil') {
    die("Pesanan ini tidak bisa diubah oleh petugas.");
}

mysqli_query($conn, "
    UPDATE laundry_orders
    SET 
        status='$new_status',
        staff_id='$staff_id'
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
        '$staff_id',
        '$old_status',
        '$new_status',
        'Status diperbarui oleh petugas laundry.'
    )
");

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