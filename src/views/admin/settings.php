<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

function getSetting($conn, $key, $default = "")
{
    $key = mysqli_real_escape_string($conn, $key);

    $query = mysqli_query($conn, "
        SELECT setting_value
        FROM settings
        WHERE setting_key='$key'
        LIMIT 1
    ");

    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        return $data['setting_value'];
    }

    return $default;
}

$success = "";

if (isset($_POST['save_settings'])) {

    $settings = [
        'app_name' => $_POST['app_name'],
        'laundry_name' => $_POST['laundry_name'],
        'admin_contact' => $_POST['admin_contact'],
        'admin_email' => $_POST['admin_email'],
        'default_payment_method' => $_POST['default_payment_method'],
        'laundry_address' => $_POST['laundry_address'],
        'opening_hours' => $_POST['opening_hours']
    ];

    foreach ($settings as $key => $value) {
        $safeKey = mysqli_real_escape_string($conn, $key);
        $safeValue = mysqli_real_escape_string($conn, $value);

        $check = mysqli_query($conn, "
            SELECT id
            FROM settings
            WHERE setting_key='$safeKey'
            LIMIT 1
        ");

        if ($check && mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "
                UPDATE settings
                SET setting_value='$safeValue'
                WHERE setting_key='$safeKey'
            ");
        } else {
            mysqli_query($conn, "
                INSERT INTO settings(setting_key, setting_value)
                VALUES('$safeKey', '$safeValue')
            ");
        }
    }

    $success = "Settings berhasil disimpan.";
}

$app_name = getSetting($conn, 'app_name', 'UMKM Sistem Informasi Pemesanan Laundry Berbasis Web');
$laundry_name = getSetting($conn, 'laundry_name', 'Laundry UMKM');
$admin_contact = getSetting($conn, 'admin_contact', '08123456789');
$admin_email = getSetting($conn, 'admin_email', 'admin@gmail.com');
$default_payment_method = getSetting($conn, 'default_payment_method', 'Cash');
$laundry_address = getSetting($conn, 'laundry_address', 'Bekasi');
$opening_hours = getSetting($conn, 'opening_hours', '08.00 - 21.00 WIB');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Settings Laundry</title>
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
                <h1 class="page-title">Settings</h1>
                <p class="page-subtitle">
                    Atur informasi dasar sistem laundry.
                </p>
            </div>

            <a href="dashboard.php" class="modern-btn-outline">
                Dashboard
            </a>
        </div>

        <?php if ($success != "") : ?>
            <div style="background:#dcfce7;color:#15803d;border-radius:18px;padding:15px 18px;margin-bottom:22px;font-weight:800;">
                <?= htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr .7fr;gap:28px;align-items:start;">

            <div class="modern-card" style="padding:30px;">

                <form method="POST">

                    <div style="margin-bottom:22px;">
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Nama Aplikasi
                        </label>
                        <input type="text" name="app_name" class="modern-input" value="<?= htmlspecialchars($app_name); ?>" required>
                    </div>

                    <div style="margin-bottom:22px;">
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Nama Laundry
                        </label>
                        <input type="text" name="laundry_name" class="modern-input" value="<?= htmlspecialchars($laundry_name); ?>" required>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;">

                        <div>
                            <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                                Kontak Admin
                            </label>
                            <input type="text" name="admin_contact" class="modern-input" value="<?= htmlspecialchars($admin_contact); ?>" required>
                        </div>

                        <div>
                            <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                                Email Admin
                            </label>
                            <input type="email" name="admin_email" class="modern-input" value="<?= htmlspecialchars($admin_email); ?>" required>
                        </div>

                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;">

                        <div>
                            <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                                Metode Pembayaran Default
                            </label>
                            <input type="text" name="default_payment_method" class="modern-input" value="<?= htmlspecialchars($default_payment_method); ?>" required>
                        </div>

                        <div>
                            <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                                Jam Operasional
                            </label>
                            <input type="text" name="opening_hours" class="modern-input" value="<?= htmlspecialchars($opening_hours); ?>" required>
                        </div>

                    </div>

                    <div style="margin-bottom:26px;">
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Alamat Laundry
                        </label>
                        <textarea name="laundry_address" rows="4" class="modern-input" required><?= htmlspecialchars($laundry_address); ?></textarea>
                    </div>

                    <button type="submit" name="save_settings" class="modern-btn">
                        Simpan Settings
                    </button>

                </form>

            </div>

            <div style="display:flex;flex-direction:column;gap:24px;">

                <div class="modern-card" style="overflow:hidden;">
                    <div style="height:210px;background:linear-gradient(135deg,#f9a8d4,#ec4899);display:flex;align-items:center;justify-content:center;color:white;">
                        <svg width="125" height="125" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M5 3h14v18H5V3Z"/>
                            <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M8 6h.01M11 6h.01"/>
                            <path stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
                        </svg>
                    </div>

                    <div style="padding:26px;">
                        <h2 style="font-size:28px;font-weight:900;color:#db2777;margin-bottom:12px;">
                            <?= htmlspecialchars($laundry_name); ?>
                        </h2>

                        <p style="color:#74677f;line-height:1.8;">
                            <?= htmlspecialchars($app_name); ?>
                        </p>
                    </div>
                </div>

                <div class="modern-card" style="padding:26px;">
                    <h2 style="font-size:26px;font-weight:900;color:#db2777;margin-bottom:18px;">
                        Informasi Kontak
                    </h2>

                    <div style="display:flex;flex-direction:column;gap:14px;color:#74677f;">
                        <p><b style="color:#db2777;">Telepon:</b> <?= htmlspecialchars($admin_contact); ?></p>
                        <p><b style="color:#db2777;">Email:</b> <?= htmlspecialchars($admin_email); ?></p>
                        <p><b style="color:#db2777;">Jam:</b> <?= htmlspecialchars($opening_hours); ?></p>
                        <p><b style="color:#db2777;">Alamat:</b> <?= htmlspecialchars($laundry_address); ?></p>
                    </div>
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