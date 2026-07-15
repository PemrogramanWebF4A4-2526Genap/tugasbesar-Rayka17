<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";
require_once __DIR__ . "/../../config/payment-storage.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if (empty($_SESSION['admin_payment_csrf'])) {
    try {
        $_SESSION['admin_payment_csrf'] = bin2hex(random_bytes(32));
    } catch (Throwable $throwable) {
        $_SESSION['admin_payment_csrf'] = hash('sha256', uniqid('', true));
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$allowedOrderFilters = ['all', 'diproses', 'dicuci', 'selesai', 'diambil'];

if (!in_array($statusFilter, $allowedOrderFilters, true)) {
    $statusFilter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $returnStatus = (string) ($_POST['return_status'] ?? 'all');

    if (!in_array($returnStatus, $allowedOrderFilters, true)) {
        $returnStatus = 'all';
    }

    if (
        $action !== 'verify_payment'
        || $csrfToken === ''
        || !hash_equals((string) $_SESSION['admin_payment_csrf'], $csrfToken)
    ) {
        header('Location: orders.php?status=' . urlencode($returnStatus) . '&verification_error=1');
        exit;
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $verificationStatus = paymentNormalizeVerificationStatus(
        (string) ($_POST['verification_status'] ?? 'pending')
    );
    $verificationNote = (string) ($_POST['verification_note'] ?? '');

    $orderExists = false;

    if ($orderId > 0) {
        $checkOrder = mysqli_query($conn, "
            SELECT id
            FROM laundry_orders
            WHERE id='$orderId'
            LIMIT 1
        ");
        $orderExists = $checkOrder && mysqli_num_rows($checkOrder) === 1;
    }

    $proof = $orderExists ? paymentGetProof($orderId) : null;
    $proofPath = $proof ? paymentProofAbsolutePath($proof) : '';

    if (!$orderExists || !$proof || $proofPath === '' || !is_file($proofPath)) {
        header('Location: orders.php?status=' . urlencode($returnStatus) . '&verification_error=proof');
        exit;
    }

    $adminId = (int) ($_SESSION['user']['id'] ?? 0);
    $adminName = (string) ($_SESSION['user']['name'] ?? 'Admin Laundry');

    if (!paymentSaveAdminVerification(
        $orderId,
        $verificationStatus,
        $adminId,
        $adminName,
        $verificationNote
    )) {
        header('Location: orders.php?status=' . urlencode($returnStatus) . '&verification_error=save');
        exit;
    }

    header('Location: orders.php?status=' . urlencode($returnStatus) . '&verification_updated=1#order-' . $orderId);
    exit;
}

$where = "WHERE 1=1";

if ($statusFilter !== 'all') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND laundry_orders.status='$safeStatus'";
}

$orders = mysqli_query($conn, "
    SELECT
        laundry_orders.*,
        laundry_services.service_name,
        laundry_services.unit,
        laundry_mitras.mitra_name,
        laundry_mitras.phone AS mitra_phone,
        staff_user.name AS staff_name,
        buyer_user.name AS buyer_name
    FROM laundry_orders
    JOIN laundry_services ON laundry_orders.service_id = laundry_services.id
    LEFT JOIN laundry_mitras ON laundry_orders.mitra_id = laundry_mitras.id
    LEFT JOIN users AS staff_user ON laundry_orders.staff_id = staff_user.id
    LEFT JOIN users AS buyer_user ON laundry_orders.user_id = buyer_user.id
    $where
    ORDER BY laundry_orders.id DESC
");

function adminOrderStatusBadge($status)
{
    $styles = [
        'diproses' => 'background:#e0f2fe;color:#0369a1;',
        'dicuci' => 'background:#dbeafe;color:#1d4ed8;',
        'selesai' => 'background:#dcfce7;color:#166534;',
        'diambil' => 'background:#f1f5f9;color:#334155;',
    ];

    $labels = [
        'diproses' => 'Diproses',
        'dicuci' => 'Dicuci',
        'selesai' => 'Selesai',
        'diambil' => 'Diambil',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}

function adminPaymentBadge($status)
{
    $styles = [
        'unpaid' => 'background:#fee2e2;color:#b91c1c;',
        'waiting_confirmation' => 'background:#fef3c7;color:#92400e;',
        'paid' => 'background:#dcfce7;color:#166534;',
        'cancelled' => 'background:#f1f5f9;color:#334155;',
    ];

    $labels = [
        'unpaid' => 'Belum Bayar',
        'waiting_confirmation' => 'Menunggu Konfirmasi',
        'paid' => 'Lunas',
        'cancelled' => 'Batal',
    ];

    return "<span class='status-pill' style='" . ($styles[$status] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$status] ?? ucfirst($status)) . "</span>";
}

function adminVerificationBadge(string $status): string
{
    $styles = [
        'pending' => 'background:#fef3c7;color:#92400e;border-color:#fde68a;',
        'valid' => 'background:#dcfce7;color:#166534;border-color:#bbf7d0;',
        'invalid' => 'background:#fee2e2;color:#b91c1c;border-color:#fecaca;',
        'not_uploaded' => 'background:#f1f5f9;color:#475569;border-color:#e2e8f0;',
    ];

    $labels = [
        'pending' => 'Menunggu Verifikasi Admin',
        'valid' => 'Bukti Valid',
        'invalid' => 'Bukti Tidak Valid',
        'not_uploaded' => 'Belum Ada Bukti',
    ];

    $safeStatus = array_key_exists($status, $labels) ? $status : 'pending';

    return "<span class='admin-verification-badge' style='" . $styles[$safeStatus] . "'>" . $labels[$safeStatus] . "</span>";
}

function adminDeliveryLabel($option)
{
    $labels = [
        'self_service' => 'Antar dan ambil sendiri',
        'pickup_only' => 'Dijemput saja',
        'delivery_only' => 'Diantar saja',
        'pickup_delivery' => 'Antar jemput'
    ];

    return $labels[$option] ?? '-';
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
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Semua Pesanan</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="soft-bg-pattern admin-panel-page">

<?php include "../layouts/admin-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/admin-topbar.php"; ?>

    <section style="padding:26px;">

        <div style="margin-bottom:22px;">
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Admin Panel</p>
            <h1 class="page-title">Semua Pesanan</h1>
            <p class="page-subtitle">Pantau pesanan, buka bukti transfer, lalu tetapkan apakah bukti pembayaran valid atau tidak valid.</p>
        </div>

        <?php if (isset($_GET['verification_updated'])) : ?>
            <div class="admin-alert admin-alert-success">
                Verifikasi bukti pembayaran berhasil disimpan tanpa mengubah database.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['verification_error'])) : ?>
            <div class="admin-alert admin-alert-error">
                Verifikasi gagal disimpan. Pastikan bukti pembayaran tersedia dan coba kembali.
            </div>
        <?php endif; ?>

        <div class="modern-card" style="padding:16px;margin-bottom:22px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php
                $filters = [
                    'all' => 'Semua',
                    'diproses' => 'Diproses',
                    'dicuci' => 'Dicuci',
                    'selesai' => 'Selesai',
                    'diambil' => 'Diambil'
                ];
                ?>

                <?php foreach ($filters as $key => $label) : ?>
                    <a href="orders.php?status=<?= $key; ?>" class="<?= $statusFilter === $key ? 'modern-btn' : 'modern-btn-outline'; ?>" style="padding:10px 16px;">
                        <?= $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:18px;">

            <?php if ($orders && mysqli_num_rows($orders) > 0) : ?>

                <?php while ($order = mysqli_fetch_assoc($orders)) : ?>
                    <?php
                    $orderId = (int) $order['id'];
                    $paymentProof = paymentGetProof($orderId);
                    $paymentProofPath = $paymentProof ? paymentProofAbsolutePath($paymentProof) : '';
                    $paymentProofAvailable = $paymentProof
                        && $paymentProofPath !== ''
                        && is_file($paymentProofPath);
                    $paymentProofUrl = '../shared/payment-proof.php?order_id=' . $orderId;
                    $verificationStatus = $paymentProofAvailable
                        ? paymentProofVerificationStatus($paymentProof)
                        : 'not_uploaded';
                    $bankAccount = paymentGetMitraBankAccount((int) ($order['mitra_id'] ?? 0));
                    ?>

                    <div class="modern-card order-card" id="order-<?= $orderId; ?>" style="padding:22px;scroll-margin-top:90px;">

                        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                            <div>
                                <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">
                                    Order #<?= $orderId; ?>
                                </p>

                                <h2 style="font-size:23px;font-weight:800;color:#0f172a;margin:0;">
                                    <?= htmlspecialchars($order['service_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </h2>

                                <p style="color:#64748b;margin-top:6px;font-size:13px;">
                                    <?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?> • Seller: <?= htmlspecialchars($order['mitra_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>

                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;">
                                <?= adminOrderStatusBadge($order['status']); ?>
                                <?= adminPaymentBadge($order['payment_status']); ?>
                                <?= adminVerificationBadge($verificationStatus); ?>
                            </div>
                        </div>

                        <div class="admin-order-summary">
                            <div class="admin-summary-item">
                                <p>Berat</p>
                                <h3><?= number_format($order['weight'] ?? 0, 2, ',', '.'); ?> <?= htmlspecialchars($order['unit'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            </div>

                            <div class="admin-summary-item">
                                <p>Harga</p>
                                <h3>Rp <?= number_format($order['price_per_kg'], 0, ',', '.'); ?></h3>
                            </div>

                            <div class="admin-summary-item">
                                <p>Delivery</p>
                                <h3>Rp <?= number_format($order['delivery_total'], 0, ',', '.'); ?></h3>
                            </div>

                            <div class="admin-summary-item">
                                <p>Total</p>
                                <h3>Rp <?= number_format($order['total_price'], 0, ',', '.'); ?></h3>
                            </div>

                            <div class="admin-summary-item">
                                <p>Petugas</p>
                                <h3><?= htmlspecialchars($order['staff_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></h3>
                            </div>
                        </div>

                        <div class="admin-order-content-grid">
                            <details class="admin-order-detail">
                                <summary>Detail Pesanan</summary>

                                <div class="admin-detail-grid">
                                    <div>
                                        <p class="admin-detail-title">Pelanggan</p>
                                        <p class="admin-detail-text">
                                            <?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                                            <?= htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?><br>
                                            <?= nl2br(htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8')); ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="admin-detail-title">Delivery</p>
                                        <p class="admin-detail-text">
                                            <?= htmlspecialchars(adminDeliveryLabel($order['delivery_option']), ENT_QUOTES, 'UTF-8'); ?><br>
                                            Pickup: Rp <?= number_format($order['pickup_fee'], 0, ',', '.'); ?><br>
                                            Antar: Rp <?= number_format($order['delivery_fee'], 0, ',', '.'); ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="admin-detail-title">Alamat Pickup</p>
                                        <p class="admin-detail-text">
                                            <?= nl2br(htmlspecialchars($order['pickup_address'] ?: '-', ENT_QUOTES, 'UTF-8')); ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="admin-detail-title">Alamat Pengantaran</p>
                                        <p class="admin-detail-text">
                                            <?= nl2br(htmlspecialchars($order['delivery_address'] ?: '-', ENT_QUOTES, 'UTF-8')); ?>
                                        </p>
                                    </div>
                                </div>

                                <div style="margin-top:14px;">
                                    <p class="admin-detail-title">Catatan</p>
                                    <p class="admin-detail-text">
                                        <?= nl2br(htmlspecialchars($order['notes'] ?: '-', ENT_QUOTES, 'UTF-8')); ?>
                                    </p>
                                </div>
                            </details>

                            <section class="admin-payment-panel">
                                <div class="admin-payment-heading">
                                    <div>
                                        <p class="admin-payment-kicker">Pembayaran</p>
                                        <h3>Verifikasi Bukti Transfer</h3>
                                    </div>
                                    <?= adminVerificationBadge($verificationStatus); ?>
                                </div>

                                <div class="admin-payment-meta">
                                    <div>
                                        <span>Metode</span>
                                        <strong><?= $order['payment_method'] === 'transfer' ? 'Transfer' : 'COD'; ?></strong>
                                    </div>
                                    <div>
                                        <span>Status Mitra</span>
                                        <strong><?= htmlspecialchars(strip_tags(adminPaymentBadge($order['payment_status'])), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <div>
                                        <span>Tagihan</span>
                                        <strong>Rp <?= number_format($order['total_price'], 0, ',', '.'); ?></strong>
                                    </div>
                                </div>

                                <?php if ($order['payment_method'] === 'transfer') : ?>
                                    <div class="admin-bank-box">
                                        <span>Rekening Tujuan</span>
                                        <?php if (paymentBankAccountComplete($bankAccount)) : ?>
                                            <strong><?= htmlspecialchars($bankAccount['bank_name'], ENT_QUOTES, 'UTF-8'); ?> — <?= htmlspecialchars($bankAccount['account_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small>a.n. <?= htmlspecialchars($bankAccount['account_holder'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php else : ?>
                                            <strong>Rekening mitra belum dilengkapi.</strong>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($paymentProofAvailable) : ?>
                                        <a href="<?= htmlspecialchars($paymentProofUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="admin-proof-link">
                                            <img
                                                src="<?= htmlspecialchars($paymentProofUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                alt="Bukti pembayaran order #<?= $orderId; ?>"
                                                class="admin-proof-image"
                                            >
                                            <span>Buka bukti pembayaran ukuran penuh</span>
                                        </a>

                                        <div class="admin-proof-information">
                                            <p>Diunggah: <strong><?= htmlspecialchars($paymentProof['uploaded_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                            <?php if (!empty($paymentProof['verified_at'])) : ?>
                                                <p>Diverifikasi: <strong><?= htmlspecialchars($paymentProof['verified_at'], ENT_QUOTES, 'UTF-8'); ?></strong> oleh <?= htmlspecialchars($paymentProof['verified_by_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <form method="POST" class="admin-verification-form">
                                            <input type="hidden" name="action" value="verify_payment">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_payment_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                                            <input type="hidden" name="return_status" value="<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8'); ?>">

                                            <label for="verification-note-<?= $orderId; ?>">Catatan Verifikasi</label>
                                            <textarea
                                                id="verification-note-<?= $orderId; ?>"
                                                name="verification_note"
                                                rows="3"
                                                placeholder="Contoh: nominal dan rekening sesuai, atau jelaskan alasan bukti ditolak."
                                            ><?= htmlspecialchars($paymentProof['verification_note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

                                            <div class="admin-verification-actions">
                                                <button type="submit" name="verification_status" value="valid" class="admin-verify-valid">
                                                    Bukti Valid
                                                </button>
                                                <button type="submit" name="verification_status" value="invalid" class="admin-verify-invalid">
                                                    Tidak Valid
                                                </button>
                                                <button type="submit" name="verification_status" value="pending" class="admin-verify-pending">
                                                    Reset Menunggu
                                                </button>
                                            </div>
                                        </form>
                                    <?php else : ?>
                                        <div class="admin-payment-empty">
                                            Pelanggan belum mengunggah bukti pembayaran untuk pesanan ini.
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="admin-payment-cod">
                                        Pesanan menggunakan COD sehingga tidak memerlukan bukti transfer.
                                    </div>
                                <?php endif; ?>
                            </section>
                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="modern-card" style="padding:36px;text-align:center;">
                    <h2 style="font-size:26px;font-weight:800;color:#0369a1;margin-bottom:10px;">
                        Belum Ada Pesanan
                    </h2>

                    <p style="color:#64748b;">
                        Pesanan pelanggan akan muncul di halaman ini.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<style>
.admin-alert {
    border-radius:18px;
    padding:14px 18px;
    font-weight:800;
    margin-bottom:22px;
}

.admin-alert-success {
    background:#dcfce7;
    color:#166534;
}

.admin-alert-error {
    background:#fee2e2;
    color:#b91c1c;
}

.admin-verification-badge {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid;
    border-radius:999px;
    padding:7px 11px;
    font-size:11px;
    font-weight:900;
    line-height:1.2;
    text-align:center;
}

.admin-order-summary {
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:12px;
    margin-bottom:16px;
}

.admin-summary-item {
    background:#f0f9ff;
    border:1px solid #bae6fd;
    border-radius:18px;
    padding:14px;
    min-width:0;
}

.admin-summary-item p {
    color:#64748b;
    font-weight:700;
    font-size:12px;
    margin:0;
}

.admin-summary-item h3 {
    font-size:16px;
    font-weight:800;
    color:#0369a1;
    margin:5px 0 0;
    overflow-wrap:anywhere;
}

.admin-order-content-grid {
    display:grid;
    grid-template-columns:minmax(0,1fr) minmax(320px,420px);
    gap:16px;
    align-items:start;
}

.admin-order-detail {
    background:#f8fdff;
    border:1px solid #d8f1ff;
    border-radius:18px;
    padding:15px;
}

.admin-order-detail summary {
    cursor:pointer;
    font-weight:800;
    color:#0369a1;
}

.admin-detail-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-top:14px;
}

.admin-detail-title {
    font-weight:800;
    color:#0369a1;
    margin:0 0 6px;
}

.admin-detail-text {
    color:#64748b;
    line-height:1.7;
    font-size:13px;
    margin:0;
    overflow-wrap:anywhere;
}

.admin-payment-panel {
    background:#f8fdff;
    border:1px solid #bae6fd;
    border-radius:20px;
    padding:18px;
    min-width:0;
}

.admin-payment-heading {
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.admin-payment-kicker {
    color:#0284c7;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.06em;
    margin:0 0 3px;
}

.admin-payment-heading h3 {
    color:#0f172a;
    font-size:18px;
    font-weight:900;
    margin:0;
}

.admin-payment-meta {
    display:grid;
    gap:7px;
    padding:12px;
    background:#fff;
    border:1px solid #e0f2fe;
    border-radius:15px;
    margin-bottom:12px;
}

.admin-payment-meta div {
    display:flex;
    justify-content:space-between;
    gap:12px;
    font-size:12px;
}

.admin-payment-meta span {
    color:#64748b;
    font-weight:700;
}

.admin-payment-meta strong {
    color:#0f172a;
    text-align:right;
}

.admin-bank-box {
    display:grid;
    gap:3px;
    background:#eff6ff;
    border:1px solid #bfdbfe;
    border-radius:15px;
    padding:12px;
    margin-bottom:12px;
}

.admin-bank-box span,
.admin-bank-box small {
    color:#64748b;
    font-size:11px;
    font-weight:700;
}

.admin-bank-box strong {
    color:#1d4ed8;
    font-size:13px;
    overflow-wrap:anywhere;
}

.admin-proof-link {
    display:block;
    text-decoration:none;
    color:#0369a1;
    font-size:12px;
    font-weight:900;
    text-align:center;
}

.admin-proof-image {
    display:block;
    width:100%;
    max-height:330px;
    object-fit:contain;
    background:#fff;
    border:1px solid #bae6fd;
    border-radius:15px;
    margin-bottom:8px;
}

.admin-proof-information {
    margin-top:10px;
    padding:10px 12px;
    border-radius:14px;
    background:#fff;
    border:1px solid #e2e8f0;
}

.admin-proof-information p {
    color:#64748b;
    font-size:11px;
    line-height:1.5;
    margin:0;
}

.admin-proof-information p + p {
    margin-top:4px;
}

.admin-verification-form {
    margin-top:12px;
}

.admin-verification-form label {
    display:block;
    color:#0369a1;
    font-size:12px;
    font-weight:900;
    margin-bottom:6px;
}

.admin-verification-form textarea {
    display:block;
    width:100%;
    resize:vertical;
    min-height:82px;
    border:1px solid #bae6fd;
    border-radius:14px;
    padding:11px 12px;
    color:#0f172a;
    background:#fff;
    font:inherit;
    font-size:12px;
    line-height:1.5;
    box-sizing:border-box;
}

.admin-verification-actions {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-top:10px;
}

.admin-verification-actions button {
    border:0;
    border-radius:12px;
    min-height:42px;
    padding:9px 11px;
    font-weight:900;
    cursor:pointer;
}

.admin-verify-valid {
    background:#16a34a;
    color:#fff;
}

.admin-verify-invalid {
    background:#dc2626;
    color:#fff;
}

.admin-verify-pending {
    grid-column:1 / -1;
    background:#e2e8f0;
    color:#334155;
}

.admin-payment-empty,
.admin-payment-cod {
    border-radius:14px;
    padding:12px;
    font-size:12px;
    font-weight:800;
    line-height:1.5;
}

.admin-payment-empty {
    background:#fff7ed;
    color:#9a3412;
    border:1px solid #fed7aa;
}

.admin-payment-cod {
    background:#f0fdf4;
    color:#166534;
    border:1px solid #bbf7d0;
}

@media (max-width: 1180px) {
    .admin-order-summary {
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .admin-order-content-grid {
        grid-template-columns:1fr;
    }
}

@media (max-width: 720px) {
    main.dashboard-main > section {
        padding:18px 14px !important;
    }

    .order-card {
        padding:16px !important;
    }

    .admin-order-summary,
    .admin-detail-grid,
    .admin-verification-actions {
        grid-template-columns:1fr;
    }

    .admin-verify-pending {
        grid-column:auto;
    }

    .admin-payment-meta div {
        align-items:flex-start;
    }

    .admin-payment-heading .admin-verification-badge {
        width:100%;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>
