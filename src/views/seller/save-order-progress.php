<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['seller', 'mitra'])) {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$mitra = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM laundry_mitras
    WHERE user_id='$user_id'
    LIMIT 1
"));

if (!$mitra) {
    header("Location: orders.php?error=1");
    exit;
}

$mitra_id = (int) $mitra['id'];

$order_id = (int) ($_POST['order_id'] ?? 0);
$weight = (float) ($_POST['weight'] ?? 0);
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'diproses');
$payment_status = mysqli_real_escape_string($conn, $_POST['payment_status'] ?? 'unpaid');
$notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

$allowedStatus = ['diproses', 'dicuci', 'selesai', 'diambil'];
$allowedPayment = ['unpaid', 'waiting_confirmation', 'paid', 'cancelled'];

if (!in_array($status, $allowedStatus) || !in_array($payment_status, $allowedPayment)) {
    header("Location: orders.php?error=1");
    exit;
}

$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        laundry_orders.*,
        laundry_services.price_per_kg AS service_price
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    WHERE laundry_orders.id='$order_id'
    AND laundry_orders.mitra_id='$mitra_id'
    LIMIT 1
"));

if (!$order) {
    header("Location: orders.php?error=1");
    exit;
}

if ($weight <= 0) {
    $weight = (float) ($order['weight'] ?? 0);
}

$price_per_kg = (int) ($order['price_per_kg'] ?: $order['service_price']);
$delivery_total = (int) ($order['delivery_total'] ?? 0);
$total_price = ($weight * $price_per_kg) + $delivery_total;

$old_status = $order['status'];
$buyer_id = (int) $order['user_id'];

mysqli_query($conn, "
    UPDATE laundry_orders
    SET
        weight='$weight',
        price_per_kg='$price_per_kg',
        total_price='$total_price',
        status='$status',
        payment_status='$payment_status',
        notes='$notes',
        updated_at=NOW()
    WHERE id='$order_id'
    AND mitra_id='$mitra_id'
");

mysqli_query($conn, "
    INSERT INTO laundry_order_status_logs(order_id,user_id,old_status,new_status,note)
    VALUES(
        '$order_id',
        '$user_id',
        '$old_status',
        '$status',
        'Seller memperbarui pesanan.'
    )
");

$payment = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id
    FROM laundry_payments
    WHERE order_id='$order_id'
    LIMIT 1
"));

$paidAtSql = $payment_status === 'paid' ? ", paid_at=IF(paid_at IS NULL, NOW(), paid_at)" : "";

if ($payment) {
    mysqli_query($conn, "
        UPDATE laundry_payments
        SET
            amount='$total_price',
            payment_status='$payment_status'
            $paidAtSql
        WHERE order_id='$order_id'
    ");
} else {
    $paidAtValue = $payment_status === 'paid' ? "NOW()" : "NULL";

    mysqli_query($conn, "
        INSERT INTO laundry_payments(order_id,user_id,payment_method,amount,payment_status,paid_at)
        VALUES('$order_id','$buyer_id','{$order['payment_method']}','$total_price','$payment_status',$paidAtValue)
    ");
}

mysqli_query($conn, "
    INSERT INTO notifications(user_id,title,message,is_read)
    VALUES(
        '$buyer_id',
        'Pesanan Laundry Diperbarui',
        'Pesanan #$order_id telah diperbarui seller. Total pembayaran: Rp " . number_format($total_price, 0, ',', '.') . ".',
        0
    )
");

header("Location: orders.php?updated=1");
exit;