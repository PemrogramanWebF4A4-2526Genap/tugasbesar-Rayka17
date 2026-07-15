<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

$user = $_SESSION['user'] ?? null;

$services = mysqli_query($conn, "
    SELECT
        laundry_services.*,
        laundry_mitras.mitra_name,
        laundry_mitras.address AS mitra_address,
        laundry_mitras.city AS mitra_city,
        laundry_mitras.phone AS mitra_phone
    FROM laundry_services
    JOIN laundry_mitras ON laundry_services.mitra_id = laundry_mitras.id
    WHERE laundry_services.status='active'
    AND laundry_mitras.status='active'
    ORDER BY laundry_services.id DESC
    LIMIT 12
");

$serviceOptions = mysqli_query($conn, "
    SELECT
        laundry_services.id,
        laundry_services.service_name,
        laundry_services.price_per_kg,
        laundry_services.unit,
        laundry_mitras.mitra_name
    FROM laundry_services
    JOIN laundry_mitras ON laundry_services.mitra_id = laundry_mitras.id
    WHERE laundry_services.status='active'
    AND laundry_mitras.status='active'
    ORDER BY laundry_services.service_name ASC
");

$mitras = mysqli_query($conn, "
    SELECT *
    FROM laundry_mitras
    WHERE status='active'
    ORDER BY id DESC
    LIMIT 6
");

function serviceImage($serviceName)
{
    $name = strtolower($serviceName);

    if (strpos($name, 'sepatu') !== false) {
        return "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'boneka') !== false) {
        return "https://images.unsplash.com/photo-1563901935883-cb61f5d49be4?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'sprei') !== false || strpos($name, 'spre') !== false) {
        return "https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'bed') !== false || strpos($name, 'cover') !== false || strpos($name, 'selimut') !== false) {
        return "https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'gorden') !== false || strpos($name, 'tirai') !== false) {
        return "https://images.unsplash.com/photo-1618221118493-9cfa1a1c00da?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'karpet') !== false) {
        return "https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'tas') !== false) {
        return "https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=900&q=80";
    }

    if (strpos($name, 'bantal') !== false || strpos($name, 'sarung') !== false) {
        return "https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=900&q=80";
    }

    return "https://images.unsplash.com/photo-1582735689369-4fe89db7114c?auto=format&fit=crop&w=900&q=80";
}

function mitraImage($index)
{
    $images = [
        "https://images.unsplash.com/photo-1582735689369-4fe89db7114c?auto=format&fit=crop&w=900&q=80",
        "https://images.unsplash.com/photo-1604335399105-a0c585fd81a1?auto=format&fit=crop&w=900&q=80",
        "https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=900&q=80",
        "https://images.unsplash.com/photo-1517677208171-0bc6725a3e60?auto=format&fit=crop&w=900&q=80"
    ];

    return $images[$index % count($images)];
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
    <title>Laundry UMKM</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f8fdff;
            color: #0f172a;
            overflow-x: hidden;
        }

        .home-page {
            width: 100%;
            overflow-x: hidden;
        }

        .hero-section {
            min-height: calc(100vh - 84px);
            padding: 54px 7%;
            background:
                linear-gradient(90deg, rgba(248,253,255,.96), rgba(224,247,255,.83)),
                url("https://images.unsplash.com/photo-1604335399105-a0c585fd81a1?auto=format&fit=crop&w=1800&q=80");
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
        }

        .hero-inner {
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 42px;
            align-items: center;
        }

        .hero-content {
            max-width: 720px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,.86);
            border: 1px solid #7dd3fc;
            color: #0369a1;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 950;
            margin-bottom: 22px;
            box-shadow: 0 14px 34px rgba(2,132,199,.09);
        }

        .hero-title {
            font-size: 62px;
            line-height: 1.03;
            font-weight: 950;
            color: #08142b;
            letter-spacing: -1.8px;
            margin: 0 0 22px;
        }

        .hero-desc {
            font-size: 18px;
            line-height: 1.9;
            color: #475569;
            margin-bottom: 26px;
            max-width: 680px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 14px;
            max-width: 660px;
        }

        .hero-stat {
            background: rgba(255,255,255,.86);
            border: 1px solid #d8f1ff;
            border-radius: 22px;
            padding: 18px;
            box-shadow: 0 14px 34px rgba(2,132,199,.08);
        }

        .hero-stat h3 {
            font-size: 26px;
            font-weight: 950;
            color: #0369a1;
            margin: 0 0 6px;
        }

        .hero-stat p {
            font-size: 13px;
            color: #64748b;
            font-weight: 750;
            margin: 0;
        }

        .reservation-card {
            width: 100%;
            background: rgba(255,255,255,.96);
            border: 1px solid #7dd3fc;
            border-radius: 30px;
            padding: 28px;
            box-shadow: 0 24px 60px rgba(2,132,199,.15);
            backdrop-filter: blur(14px);
        }

        .reservation-card h2 {
            font-size: 31px;
            line-height: 1.12;
            font-weight: 950;
            color: #075985;
            margin: 0 0 12px;
        }

        .reservation-card p {
            color: #64748b;
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 20px;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .quick-label {
            display: block;
            font-size: 13px;
            font-weight: 900;
            color: #0369a1;
            margin-bottom: 7px;
        }

        .quick-input {
            width: 100%;
            border: 1px solid #bae6fd;
            background: #f8fdff;
            border-radius: 16px;
            padding: 14px 15px;
            outline: none;
            color: #0f172a;
            font-weight: 750;
            transition: .2s;
        }

        .quick-input:focus {
            background: white;
            border-color: #0284c7;
            box-shadow: 0 0 0 4px rgba(14,165,233,.12);
        }

        .section-ui {
            max-width: 1240px;
            margin: 0 auto;
            padding: 58px 7%;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 26px;
        }

        .section-kicker {
            font-weight: 950;
            color: #0284c7;
            margin: 0 0 8px;
        }

        .section-title {
            font-size: 38px;
            line-height: 1.13;
            font-weight: 950;
            color: #08142b;
            letter-spacing: -1px;
            margin: 0;
        }

        .section-subtitle {
            color: #64748b;
            line-height: 1.7;
            margin-top: 9px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 18px;
        }

        .service-card {
            background: white;
            border: 1px solid #bae6fd;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 14px 34px rgba(2,132,199,.10);
            transition: .25s;
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 56px rgba(2,132,199,.17);
        }

        .service-img-wrap {
            width: 100%;
            height: 118px;
            overflow: hidden;
            background: #dff5ff;
            border-radius: 18px;
            margin: 14px auto 0;
            width: calc(100% - 28px);
        }

        .service-img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center;
            transition: .4s;
        }

        .service-card:hover .service-img {
            transform: scale(1.06);
        }

        .service-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .service-title {
            font-size: 20px;
            line-height: 1.22;
            font-weight: 950;
            color: #08142b;
            margin: 0 0 9px;
        }

        .service-desc {
            color: #64748b;
            line-height: 1.65;
            font-size: 13px;
            margin-bottom: 16px;
            flex: 1;
        }

        .price-box {
            border-top: 1px solid #d8f1ff;
            padding-top: 13px;
            margin-top: auto;
        }

        .price-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 900;
            margin: 0;
        }

        .price {
            font-size: 22px;
            font-weight: 950;
            color: #0369a1;
            margin-top: 4px;
        }

        .price span {
            font-size: 12px;
            color: #64748b;
            font-weight: 900;
        }

        .estimate {
            font-size: 12px;
            color: #64748b;
            margin-top: 7px;
            font-weight: 750;
        }

        .service-action {
            margin-top: 15px;
        }

        .service-action a {
            width: 100%;
            justify-content: center;
            display: inline-flex;
            padding: 11px 16px !important;
            font-size: 13px;
        }

        .mitra-section {
            background:
                radial-gradient(circle at top left, rgba(14,165,233,.15), transparent 36%),
                #eefbff;
            padding-top: 60px;
            padding-bottom: 72px;
        }

        .mitra-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 22px;
        }

        .mitra-card {
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid #bae6fd;
            background: white;
            box-shadow: 0 18px 44px rgba(2,132,199,.10);
            transition: .25s;
        }

        .mitra-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 26px 62px rgba(2,132,199,.17);
        }

        .mitra-img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: block;
            background: #dff5ff;
        }

        .mitra-body {
            padding: 22px;
        }

        .mitra-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #e0f2fe;
            color: #0369a1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 950;
            border: 4px solid white;
            margin-top: -48px;
            position: relative;
        }

        .mitra-name {
            font-size: 22px;
            line-height: 1.25;
            font-weight: 950;
            color: #08142b;
            margin: 13px 0 8px;
        }

        .mitra-text {
            color: #64748b;
            line-height: 1.7;
            font-size: 14px;
            margin: 0;
        }

        .mitra-phone {
            color: #0369a1;
            font-weight: 950;
            margin-top: 13px;
        }

        .cta-section {
            padding: 56px 7% 76px;
            background: #f8fdff;
        }

        .cta-card {
            max-width: 1180px;
            margin: 0 auto;
            background:
                linear-gradient(135deg, rgba(2,132,199,.96), rgba(14,165,233,.84)),
                url("https://images.unsplash.com/photo-1582735689369-4fe89db7114c?auto=format&fit=crop&w=1600&q=80");
            background-size: cover;
            background-position: center;
            border-radius: 34px;
            padding: 44px;
            color: white;
            box-shadow: 0 26px 60px rgba(2,132,199,.23);
        }

        .cta-card h2 {
            font-size: 38px;
            line-height: 1.14;
            font-weight: 950;
            margin: 0 0 12px;
            max-width: 850px;
        }

        .cta-card p {
            color: rgba(255,255,255,.88);
            line-height: 1.8;
            margin-bottom: 24px;
            max-width: 720px;
        }

        .btn-white {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #0369a1;
            border-radius: 999px;
            padding: 13px 22px;
            font-weight: 950;
            text-decoration: none;
            box-shadow: 0 16px 36px rgba(15,23,42,.18);
        }

        .mobile-reserve-float {
            display: none;
        }

        @media (max-width: 1160px) {
            .hero-inner {
                grid-template-columns: 1fr 390px;
                gap: 30px;
            }

            .hero-title {
                font-size: 50px;
            }

            .service-grid {
                grid-template-columns: repeat(3, minmax(0,1fr));
            }
        }

        @media (max-width: 960px) {
            .hero-section {
                align-items: flex-start;
            }

            .hero-inner {
                grid-template-columns: 1fr;
            }

            .reservation-card {
                max-width: 480px;
                justify-self: start;
            }

            .hero-content {
                max-width: 100%;
                order: 1;
            }

            .reservation-card {
                order: 2;
            }

            .hero-title {
                font-size: 44px;
            }

            .mitra-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .service-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }
        }

        @media (max-width: 650px) {
            .hero-section {
                padding: 28px 18px 42px;
                min-height: auto;
            }

            .section-ui {
                padding: 42px 18px;
            }

            .reservation-card {
                max-width: 100%;
                padding: 22px;
                border-radius: 24px;
            }

            .reservation-card h2 {
                font-size: 26px;
            }

            .hero-title {
                font-size: 36px;
                letter-spacing: -1px;
            }

            .hero-desc {
                font-size: 15px;
            }

            .hero-stats {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 29px;
            }

            .service-grid,
            .mitra-grid {
                grid-template-columns: 1fr;
            }

            .service-img-wrap {
                height: 170px;
            }

            .mitra-img {
                height: 220px;
            }

            .cta-section {
                padding: 42px 18px 62px;
            }

            .cta-card {
                padding: 28px;
                border-radius: 26px;
            }

            .cta-card h2 {
                font-size: 29px;
            }

            .mobile-reserve-float {
                display: inline-flex;
                position: fixed;
                right: 18px;
                bottom: 18px;
                z-index: 50;
                background: linear-gradient(135deg, #0284c7, #0ea5e9);
                color: white;
                border-radius: 999px;
                padding: 13px 18px;
                font-weight: 950;
                text-decoration: none;
                box-shadow: 0 18px 40px rgba(2,132,199,.32);
            }
        }
    </style>
</head>

<body class="public-home-page">

<?php include "../layouts/buyer-navbar.php"; ?>

<div class="home-page">

    <section class="hero-section" id="reservasi">
        <div class="hero-inner">

            <div class="hero-content">
                <div class="hero-badge">
                    ✦ Laundry UMKM Online
                </div>

                <h1 class="hero-title">
                    Cucian bersih tanpa ribet.
                </h1>

                <p class="hero-desc">
                    Pilih seller laundry, pilih layanan sesuai kebutuhan, lalu petugas membantu proses pickup, pengerjaan, dan pengantaran.
                </p>

                <div class="hero-actions">
                    <?php if ($user && $user['role'] === 'buyer') : ?>
                        <a href="../buyer/create-order.php" class="modern-btn">
                            Buat Pesanan
                        </a>

                        <a href="../buyer/orders.php" class="modern-btn-outline">
                            Lihat Pesanan
                        </a>
                    <?php else : ?>
                        <a href="login.php" class="modern-btn">
                            Mulai Pesan
                        </a>

                        <a href="register.php" class="modern-btn-outline">
                            Daftar Pelanggan
                        </a>
                    <?php endif; ?>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3>Pickup</h3>
                        <p>Petugas jemput cucian</p>
                    </div>

                    <div class="hero-stat">
                        <h3>Clean</h3>
                        <p>Cucian bersih wangi</p>
                    </div>

                    <div class="hero-stat">
                        <h3>Track</h3>
                        <p>Status bisa dipantau</p>
                    </div>
                </div>
            </div>

            <div class="reservation-card">
                <h2>Reservasi Laundry Cepat</h2>

                <p>
                    Pilih layanan laundry, isi data singkat, lalu lanjutkan pesanan. Cocok untuk cucian harian, sepatu, boneka, sprei, karpet, dan lainnya.
                </p>

                <?php if ($user && $user['role'] === 'buyer') : ?>
                    <form action="../buyer/create-order.php" method="GET">
                        <div class="quick-grid">
                            <div>
                                <label class="quick-label">Nama Pelanggan</label>
                                <input type="text" class="quick-input" value="<?= htmlspecialchars($user['name'] ?? ''); ?>" readonly>
                            </div>

                            <div>
                                <label class="quick-label">Pilih Layanan</label>
                                <select name="service_id" class="quick-input">
                                    <option value="">Pilih layanan laundry</option>

                                    <?php if ($serviceOptions && mysqli_num_rows($serviceOptions) > 0) : ?>
                                        <?php while ($option = mysqli_fetch_assoc($serviceOptions)) : ?>
                                            <option value="<?= $option['id']; ?>">
                                                <?= htmlspecialchars($option['service_name']); ?> - Rp <?= number_format($option['price_per_kg'], 0, ',', '.'); ?>/<?= htmlspecialchars($option['unit']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div>
                                <label class="quick-label">Catatan Singkat</label>
                                <input type="text" name="quick_note" class="quick-input" placeholder="Contoh: sepatu putih, boneka besar, sprei king size">
                            </div>

                            <button type="submit" class="modern-btn" style="width:100%;justify-content:center;margin-top:4px;">
                                Lanjut Buat Pesanan
                            </button>
                        </div>
                    </form>
                <?php else : ?>
                    <div class="quick-grid">
                        <div>
                            <label class="quick-label">Nama Pelanggan</label>
                            <input type="text" class="quick-input" placeholder="Masukkan nama kamu">
                        </div>

                        <div>
                            <label class="quick-label">Pilih Layanan</label>
                            <select class="quick-input">
                                <option>Pilih layanan laundry</option>
                                <option>Laundry Sepatu</option>
                                <option>Laundry Boneka</option>
                                <option>Laundry Sprei</option>
                                <option>Laundry Bed Cover</option>
                                <option>Laundry Karpet</option>
                            </select>
                        </div>

                        <a href="login.php" class="modern-btn" style="width:100%;justify-content:center;margin-top:4px;">
                            Login untuk Reservasi
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>


    <section class="section-ui">
        <div class="section-head">
            <div>
                <p class="section-kicker">Layanan Laundry</p>

                <h2 class="section-title">
                    Pilih layanan sesuai cucian
                </h2>

                <p class="section-subtitle">
                    Setiap layanan memiliki gambar, harga, satuan, dan estimasi pengerjaan.
                </p>
            </div>

            <?php if ($user && $user['role'] === 'buyer') : ?>
                <a href="../buyer/create-order.php" class="modern-btn">
                    Pesan Sekarang
                </a>
            <?php endif; ?>
        </div>

        <div class="service-grid">
            <?php if ($services && mysqli_num_rows($services) > 0) : ?>

                <?php while ($service = mysqli_fetch_assoc($services)) : ?>

                    <div class="service-card">
                        <div class="service-img-wrap">
                            <img src="<?= serviceImage($service['service_name']); ?>" class="service-img" alt="<?= htmlspecialchars($service['service_name']); ?>">
                        </div>

                        <div class="service-body">
                            <h3 class="service-title">
                                <?= htmlspecialchars($service['service_name']); ?>
                            </h3>

                            <p class="service-desc">
                                <?= htmlspecialchars($service['description'] ?: 'Layanan laundry bersih, rapi, dan wangi sesuai kebutuhan cucian kamu.'); ?>
                            </p>

                            <div class="price-box">
                                <p class="price-label">Harga mulai</p>

                                <div class="price">
                                    Rp <?= number_format($service['price_per_kg'] ?? 0, 0, ',', '.'); ?>
                                    <span>/ <?= htmlspecialchars($service['unit'] ?? 'kg'); ?></span>
                                </div>

                                <p class="estimate">
                                    Estimasi: <?= htmlspecialchars($service['estimated_time'] ?: '-'); ?>
                                </p>
                            </div>

                            <div class="service-action">
                                <?php if ($user && $user['role'] === 'buyer') : ?>
                                    <a href="../buyer/create-order.php?service_id=<?= $service['id']; ?>" class="modern-btn">
                                        Pilih Layanan
                                    </a>
                                <?php else : ?>
                                    <a href="login.php" class="modern-btn">
                                        Login untuk Pesan
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="modern-card" style="padding:34px;text-align:center;grid-column:1/-1;">
                    <h3 style="font-size:24px;font-weight:950;color:#0369a1;margin-bottom:8px;">
                        Belum Ada Layanan
                    </h3>

                    <p style="color:#64748b;">
                        Layanan akan muncul setelah seller menambahkan layanan.
                    </p>
                </div>

            <?php endif; ?>
        </div>
    </section>


    <section class="mitra-section">
        <div class="section-ui" style="padding-top:0;padding-bottom:0;">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Seller Laundry</p>

                    <h2 class="section-title">
                        Mitra laundry pilihan
                    </h2>

                    <p class="section-subtitle">
                        Pilih seller laundry aktif yang tersedia di sistem.
                    </p>
                </div>

                <?php if ($user && $user['role'] === 'buyer') : ?>
                    <a href="../buyer/create-order.php" class="modern-btn">
                        Pesan Sekarang
                    </a>
                <?php endif; ?>
            </div>

            <div class="mitra-grid">
                <?php if ($mitras && mysqli_num_rows($mitras) > 0) : ?>

                    <?php $i = 0; ?>
                    <?php while ($mitra = mysqli_fetch_assoc($mitras)) : ?>

                        <div class="mitra-card">
                            <img src="<?= mitraImage($i); ?>" class="mitra-img" alt="<?= htmlspecialchars($mitra['mitra_name']); ?>">

                            <div class="mitra-body">
                                <div class="mitra-icon">
                                    <?= strtoupper(substr($mitra['mitra_name'], 0, 1)); ?>
                                </div>

                                <h3 class="mitra-name">
                                    <?= htmlspecialchars($mitra['mitra_name']); ?>
                                </h3>

                                <p class="mitra-text">
                                    <?= htmlspecialchars($mitra['city'] ?: $mitra['address'] ?: 'Lokasi laundry tersedia'); ?>
                                </p>

                                <div class="mitra-phone">
                                    <?= htmlspecialchars($mitra['phone'] ?: '-'); ?>
                                </div>
                            </div>
                        </div>

                        <?php $i++; ?>

                    <?php endwhile; ?>

                <?php else : ?>

                    <div class="modern-card" style="padding:34px;text-align:center;grid-column:1/-1;">
                        <h3 style="font-size:24px;font-weight:950;color:#0369a1;margin-bottom:8px;">
                            Belum Ada Mitra Aktif
                        </h3>

                        <p style="color:#64748b;">
                            Data mitra laundry akan muncul setelah admin mengaktifkan seller.
                        </p>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </section>


    <section class="cta-section">
        <div class="cta-card">
            <h2>
                Laundry sepatu, boneka, sprei, karpet, dan cucian harian dalam satu sistem.
            </h2>

            <p>
                Sistem ini membantu pelanggan membuat pesanan, seller mengelola layanan, petugas menangani pickup dan delivery, serta customer service menangani keluhan.
            </p>

            <?php if ($user && $user['role'] === 'buyer') : ?>
                <a href="../buyer/create-order.php" class="btn-white">
                    Buat Pesanan Sekarang
                </a>
            <?php else : ?>
                <a href="login.php" class="btn-white">
                    Mulai Sekarang
                </a>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php if ($user && $user['role'] === 'buyer') : ?>
    <a href="#reservasi" class="mobile-reserve-float">
        Reservasi
    </a>
<?php endif; ?>

<script src="../../assets/js/modern.js"></script>

</body>
</html>