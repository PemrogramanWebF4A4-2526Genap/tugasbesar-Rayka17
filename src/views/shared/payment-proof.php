<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/route-helper.php';
require_once __DIR__ . '/../../config/payment-storage.php';

$orderId = (int) ($_GET['order_id'] ?? 0);
$currentUser = appCurrentUser();
$userId = (int) ($currentUser['id'] ?? 0);
$role = appNormalizeRole((string) ($currentUser['role'] ?? ''));

if ($orderId < 1 || $userId < 1) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$order = null;
$orderResult = mysqli_query($conn, "
    SELECT id, user_id, mitra_id
    FROM laundry_orders
    WHERE id='$orderId'
    LIMIT 1
");

if ($orderResult) {
    $order = mysqli_fetch_assoc($orderResult);
}

if (!$order) {
    http_response_code(404);
    exit('Bukti pembayaran tidak ditemukan.');
}

$allowed = false;

if (in_array($role, ['buyer', 'pelanggan', 'customer'], true)) {
    $allowed = (int) $order['user_id'] === $userId;
} elseif (in_array($role, ['seller', 'mitra', 'penjual'], true)) {
    $mitraResult = mysqli_query($conn, "
        SELECT id
        FROM laundry_mitras
        WHERE user_id='$userId'
        LIMIT 1
    ");
    $mitra = $mitraResult ? mysqli_fetch_assoc($mitraResult) : null;
    $allowed = $mitra && (int) $mitra['id'] === (int) $order['mitra_id'];
} elseif (in_array($role, ['admin', 'customer_service', 'cs'], true)) {
    $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$proof = paymentGetProof($orderId);
$path = $proof ? paymentProofAbsolutePath($proof) : '';

if (!$proof || $path === '' || !is_file($path)) {
    http_response_code(404);
    exit('Bukti pembayaran tidak ditemukan.');
}

$mimeType = (string) ($proof['mime_type'] ?? 'application/octet-stream');
$fileName = basename((string) ($proof['original_name'] ?? 'bukti-pembayaran'));

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . addcslashes($fileName, '"\\') . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

readfile($path);
exit;
