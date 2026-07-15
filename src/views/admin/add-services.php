<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

$error = "";

if (isset($_POST['save'])) {
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $price_per_kg = mysqli_real_escape_string($conn, $_POST['price_per_kg']);
    $estimated_time = mysqli_real_escape_string($conn, $_POST['estimated_time']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if ($service_name == "" || $price_per_kg == "" || $estimated_time == "") {
        $error = "Nama layanan, harga, dan estimasi wajib diisi.";
    } elseif ($price_per_kg <= 0) {
        $error = "Harga per kg harus lebih dari 0.";
    } else {
        mysqli_query($conn, "
            INSERT INTO laundry_services (
                service_name,
                price_per_kg,
                estimated_time,
                description,
                status
            ) VALUES (
                '$service_name',
                '$price_per_kg',
                '$estimated_time',
                '$description',
                '$status'
            )
        ");

        header("Location: services.php");
        exit;
    }
}
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
    <title>Tambah Layanan Laundry</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body class="soft-bg-pattern admin-panel-page">

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<?php include "../layouts/admin-sidebar.php"; ?>

<main class="dashboard-main">

    <?php include "../layouts/admin-topbar.php"; ?>

    <section style="padding:32px;">

        <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:28px;flex-wrap:wrap;">
            <div>
                <h1 class="page-title">Tambah Layanan</h1>
                <p class="page-subtitle">
                    Tambahkan layanan laundry baru untuk pelanggan.
                </p>
            </div>

            <a href="services.php" class="modern-btn-outline">
                Kembali
            </a>
        </div>

        <?php if ($error != "") : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:18px;padding:15px 18px;margin-bottom:22px;font-weight:700;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr .7fr;gap:28px;align-items:start;">

            <div class="modern-card" style="padding:30px;">
                <form method="POST">

                    <div style="margin-bottom:22px;">
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Nama Layanan
                        </label>
                        <input type="text" name="service_name" class="modern-input" placeholder="Contoh: Cuci Setrika" required>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;">
                        <div>
                            <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                                Harga per Kg
                            </label>
                            <input type="number" name="price_per_kg" class="modern-input" placeholder="Contoh: 9000" required>
                        </div>

                        <div>
                            <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                                Estimasi Waktu
                            </label>
                            <input type="text" name="estimated_time" class="modern-input" placeholder="Contoh: 2 Hari" required>
                        </div>
                    </div>

                    <div style="margin-bottom:22px;">
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Status
                        </label>
                        <select name="status" class="modern-input" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div style="margin-bottom:26px;">
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Deskripsi
                        </label>
                        <textarea name="description" rows="5" class="modern-input" placeholder="Tuliskan deskripsi layanan..." required></textarea>
                    </div>

                    <button type="submit" name="save" class="modern-btn">
                        Simpan Layanan
                    </button>

                </form>
            </div>

            <div class="modern-card" style="overflow:hidden;">
                <div style="height:230px;background:linear-gradient(135deg,#f9a8d4,#ec4899);display:flex;align-items:center;justify-content:center;color:white;">
                    <svg width="125" height="125" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M5 3h14v18H5V3Z"/>
                        <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M8 6h.01M11 6h.01"/>
                        <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
                    </svg>
                </div>

                <div style="padding:26px;">
                    <h2 style="font-size:28px;font-weight:900;color:#db2777;margin-bottom:12px;">
                        Layanan Laundry
                    </h2>
                    <p style="color:#74677f;line-height:1.8;">
                        Layanan yang aktif akan tampil di halaman utama dan bisa dipilih pelanggan saat membuat pesanan.
                    </p>
                </div>
            </div>

        </div>

    </section>

</main>

<style>
@media (max-width:1024px){
    section > div[style*="grid-template-columns:1fr .7fr"]{
        grid-template-columns:1fr!important;
    }
}
@media (max-width:768px){
    form div[style*="grid-template-columns:1fr 1fr"]{
        grid-template-columns:1fr!important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>