<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];

$mitras = mysqli_query($conn, "
    SELECT *
    FROM laundry_mitras
    WHERE status='active'
    ORDER BY mitra_name ASC
");

$services = mysqli_query($conn, "
    SELECT 
        laundry_services.*,
        laundry_mitras.mitra_name,
        laundry_mitras.pickup_fee,
        laundry_mitras.delivery_fee
    FROM laundry_services
    JOIN laundry_mitras ON laundry_services.mitra_id = laundry_mitras.id
    WHERE laundry_services.status='active'
    AND laundry_mitras.status='active'
    ORDER BY laundry_mitras.mitra_name ASC, laundry_services.service_name ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mitra_id = (int) ($_POST['mitra_id'] ?? 0);
    $service_id = (int) ($_POST['service_id'] ?? 0);

    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $pickup_address = mysqli_real_escape_string($conn, $_POST['pickup_address'] ?? '');
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address'] ?? '');
    $delivery_option = mysqli_real_escape_string($conn, $_POST['delivery_option'] ?? 'self_service');
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cod');
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    $allowedDelivery = ['self_service', 'pickup_only', 'delivery_only', 'pickup_delivery'];
    $allowedPayment = ['cod', 'transfer'];

    if (
        $mitra_id <= 0 ||
        $service_id <= 0 ||
        $customer_name === '' ||
        $phone === '' ||
        $address === '' ||
        !in_array($delivery_option, $allowedDelivery) ||
        !in_array($payment_method, $allowedPayment)
    ) {
        header("Location: create-order.php?error=1");
        exit;
    }

    $mitra = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM laundry_mitras
        WHERE id='$mitra_id'
        AND status='active'
        LIMIT 1
    "));

    $service = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM laundry_services
        WHERE id='$service_id'
        AND mitra_id='$mitra_id'
        AND status='active'
        LIMIT 1
    "));

    if (!$mitra || !$service) {
        header("Location: create-order.php?error=1");
        exit;
    }

    $pickup_fee = 0;
    $delivery_fee = 0;

    if ($delivery_option === 'pickup_only') {
        $pickup_fee = (int) $mitra['pickup_fee'];
    }

    if ($delivery_option === 'delivery_only') {
        $delivery_fee = (int) $mitra['delivery_fee'];
    }

    if ($delivery_option === 'pickup_delivery') {
        $pickup_fee = (int) $mitra['pickup_fee'];
        $delivery_fee = (int) $mitra['delivery_fee'];
    }

    $delivery_total = $pickup_fee + $delivery_fee;
    $price_per_kg = (int) $service['price_per_kg'];

    mysqli_query($conn, "
        INSERT INTO laundry_orders(
            user_id,
            service_id,
            mitra_id,
            staff_id,
            customer_name,
            phone,
            address,
            weight,
            price_per_kg,
            total_price,
            status,
            notes,
            delivery_option,
            pickup_fee,
            delivery_fee,
            delivery_total,
            payment_method,
            payment_status,
            pickup_address,
            delivery_address
        )
        VALUES(
            '$user_id',
            '$service_id',
            '$mitra_id',
            NULL,
            '$customer_name',
            '$phone',
            '$address',
            0,
            '$price_per_kg',
            '$delivery_total',
            'diproses',
            '$notes',
            '$delivery_option',
            '$pickup_fee',
            '$delivery_fee',
            '$delivery_total',
            '$payment_method',
            'unpaid',
            '$pickup_address',
            '$delivery_address'
        )
    ");

    if (mysqli_error($conn)) {
        die("SQL Order Error: " . mysqli_error($conn));
    }

    $order_id = mysqli_insert_id($conn);

    mysqli_query($conn, "
        INSERT INTO laundry_payments(
            order_id,
            user_id,
            payment_method,
            amount,
            payment_status
        )
        VALUES(
            '$order_id',
            '$user_id',
            '$payment_method',
            '$delivery_total',
            'unpaid'
        )
    ");

    mysqli_query($conn, "
        INSERT INTO laundry_order_status_logs(order_id,user_id,old_status,new_status,note)
        VALUES(
            '$order_id',
            '$user_id',
            NULL,
            'diproses',
            'Pesanan dibuat pelanggan.'
        )
    ");

    if ($delivery_option === 'pickup_only' || $delivery_option === 'pickup_delivery') {
        $taskAddress = $pickup_address !== '' ? $pickup_address : $address;

        mysqli_query($conn, "
            INSERT INTO laundry_staff_tasks(
                order_id,
                staff_id,
                task_type,
                task_status,
                address,
                fee,
                note
            )
            VALUES(
                '$order_id',
                NULL,
                'pickup',
                'waiting',
                '$taskAddress',
                '$pickup_fee',
                'Tugas penjemputan laundry.'
            )
        ");
    }

    if ($delivery_option === 'delivery_only' || $delivery_option === 'pickup_delivery') {
        $taskAddress = $delivery_address !== '' ? $delivery_address : $address;

        mysqli_query($conn, "
            INSERT INTO laundry_staff_tasks(
                order_id,
                staff_id,
                task_type,
                task_status,
                address,
                fee,
                note
            )
            VALUES(
                '$order_id',
                NULL,
                'delivery',
                'waiting',
                '$taskAddress',
                '$delivery_fee',
                'Tugas pengantaran laundry.'
            )
        ");
    }

    mysqli_query($conn, "
        INSERT INTO notifications(user_id,title,message,is_read)
        VALUES(
            '$user_id',
            'Pesanan Berhasil Dibuat',
            'Pesanan laundry #$order_id berhasil dibuat dan menunggu pengecekan seller.',
            0
        )
    ");

    if (!empty($mitra['user_id'])) {
        mysqli_query($conn, "
            INSERT INTO notifications(user_id,title,message,is_read)
            VALUES(
                '{$mitra['user_id']}',
                'Pesanan Baru',
                'Pesanan laundry #$order_id masuk dari $customer_name.',
                0
            )
        ");
    }

    header("Location: orders.php?created=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Buat Pesanan Laundry</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="soft-bg-pattern buyer-panel-page buyer-create-order-page">

<?php include "../layouts/buyer-navbar.php"; ?>

<section style="padding:34px 7%;">

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:24px;">
        <div>
            <p style="font-weight:800;color:#0284c7;margin-bottom:6px;">Pelanggan</p>
            <h1 class="page-title">Buat Pesanan Laundry</h1>
            <p class="page-subtitle">Pilih seller, layanan, dan metode antar jemput.</p>
        </div>

        <a href="orders.php" class="modern-btn-outline">
            Pesanan Saya
        </a>
    </div>

    <?php if (isset($_GET['error'])) : ?>
        <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:14px 18px;font-weight:800;margin-bottom:22px;">
            Gagal membuat pesanan. Pastikan data sudah lengkap.
        </div>
    <?php endif; ?>

    <div class="modern-card" style="padding:24px;">
        <form method="POST">

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Nama Pelanggan</label>
                    <input type="text" name="customer_name" class="modern-input" value="<?= htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">No. Telepon</label>
                    <input type="text" name="phone" class="modern-input" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Seller Laundry</label>
                    <select name="mitra_id" id="mitra_id" class="modern-input" required>
                        <option value="">Pilih seller</option>

                        <?php if ($mitras && mysqli_num_rows($mitras) > 0) : ?>
                            <?php while ($mitra = mysqli_fetch_assoc($mitras)) : ?>
                                <option 
                                    value="<?= $mitra['id']; ?>"
                                    data-pickup="<?= (int) $mitra['pickup_fee']; ?>"
                                    data-delivery="<?= (int) $mitra['delivery_fee']; ?>"
                                >
                                    <?= htmlspecialchars($mitra['mitra_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Layanan</label>
                    <select name="service_id" id="service_id" class="modern-input" required>
                        <option value="">Pilih layanan</option>

                        <?php if ($services && mysqli_num_rows($services) > 0) : ?>
                            <?php while ($service = mysqli_fetch_assoc($services)) : ?>
                                <option 
                                    value="<?= $service['id']; ?>"
                                    data-mitra="<?= $service['mitra_id']; ?>"
                                    data-price="<?= (int) $service['price_per_kg']; ?>"
                                    style="display:none;"
                                >
                                    <?= htmlspecialchars($service['service_name']); ?> - Rp <?= number_format($service['price_per_kg'], 0, ',', '.'); ?>/<?= htmlspecialchars($service['unit']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="grid-column:1/-1;">
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat Utama</label>
                    <textarea name="address" rows="3" class="modern-input" required><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Opsi Delivery</label>
                    <select name="delivery_option" id="delivery_option" class="modern-input" required>
                        <option value="self_service">Antar dan ambil sendiri</option>
                        <option value="pickup_only">Dijemput saja</option>
                        <option value="delivery_only">Diantar saja</option>
                        <option value="pickup_delivery">Antar jemput</option>
                    </select>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Metode Pembayaran</label>
                    <select name="payment_method" class="modern-input" required>
                        <option value="cod">COD</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat Pickup</label>
                    <textarea name="pickup_address" rows="3" class="modern-input" placeholder="Kosongkan jika sama dengan alamat utama"></textarea>
                </div>

                <div>
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Alamat Pengantaran</label>
                    <textarea name="delivery_address" rows="3" class="modern-input" placeholder="Kosongkan jika sama dengan alamat utama"></textarea>
                </div>

                <div style="grid-column:1/-1;">
                    <label style="font-weight:800;color:#0369a1;margin-bottom:7px;display:block;">Catatan</label>
                    <textarea name="notes" rows="3" class="modern-input" placeholder="Contoh: jangan pakai pewangi terlalu kuat"></textarea>
                </div>

            </div>

            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:18px;padding:16px;margin-top:18px;">
                <p style="font-weight:800;color:#0369a1;margin-bottom:6px;">Estimasi Awal</p>

                <p style="color:#64748b;line-height:1.7;font-size:14px;">
                    Harga laundry final dihitung seller setelah cucian ditimbang. Biaya awal hanya biaya pickup atau delivery.
                </p>

                <h3 id="deliveryPreview" style="font-size:22px;font-weight:900;color:#0369a1;margin-top:10px;">
                    Biaya delivery: Rp 0
                </h3>
            </div>

            <button type="submit" class="modern-btn" style="margin-top:20px;width:100%;">
                Buat Pesanan
            </button>

        </form>
    </div>

</section>

<style>
@media (max-width: 900px) {
    form div[style*="grid-template-columns:repeat(2,1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
const mitraSelect = document.getElementById('mitra_id');
const serviceSelect = document.getElementById('service_id');
const deliveryOption = document.getElementById('delivery_option');
const deliveryPreview = document.getElementById('deliveryPreview');

function filterServices() {
    const mitraId = mitraSelect.value;

    Array.from(serviceSelect.options).forEach(option => {
        if (!option.value) {
            option.style.display = 'block';
            return;
        }

        option.style.display = option.dataset.mitra === mitraId ? 'block' : 'none';
    });

    serviceSelect.value = '';
    updateDeliveryPreview();
}

function updateDeliveryPreview() {
    const selected = mitraSelect.options[mitraSelect.selectedIndex];

    const pickupFee = parseInt(selected?.dataset?.pickup || 0);
    const deliveryFee = parseInt(selected?.dataset?.delivery || 0);

    let total = 0;

    if (deliveryOption.value === 'pickup_only') {
        total = pickupFee;
    }

    if (deliveryOption.value === 'delivery_only') {
        total = deliveryFee;
    }

    if (deliveryOption.value === 'pickup_delivery') {
        total = pickupFee + deliveryFee;
    }

    deliveryPreview.innerText = 'Biaya delivery: Rp ' + total.toLocaleString('id-ID');
}

mitraSelect.addEventListener('change', filterServices);
deliveryOption.addEventListener('change', updateDeliveryPreview);
</script>

<script src="../../assets/js/modern.js"></script>

</body>
</html>