<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: services.php");
    exit;
}

$id = (int) $_GET['id'];
$error = "";

$service = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM laundry_services
    WHERE id='$id'
"));

if (!$service) {
    die("Layanan tidak ditemukan.");
}

if (isset($_POST['update'])) {
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
            UPDATE laundry_services
            SET
                service_name='$service_name',
                price_per_kg='$price_per_kg',
                estimated_time='$estimated_time',
                description='$description',
                status='$status'
            WHERE id='$id'
        ");

        header("Location: services.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Edit Layanan Laundry</title>
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
                <h1 class="page-title">Edit Layanan</h1>
                <p class="page-subtitle">
                    Ubah informasi layanan laundry.
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

        <div class="modern-card" style="padding:30px;max-width:850px;">

            <form method="POST">

                <div style="margin-bottom:22px;">
                    <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                        Nama Layanan
                    </label>
                    <input type="text" name="service_name" class="modern-input" value="<?= htmlspecialchars($service['service_name']); ?>" required>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;">
                    <div>
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Harga per Kg
                        </label>
                        <input type="number" name="price_per_kg" class="modern-input" value="<?= $service['price_per_kg']; ?>" required>
                    </div>

                    <div>
                        <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                            Estimasi Waktu
                        </label>
                        <input type="text" name="estimated_time" class="modern-input" value="<?= htmlspecialchars($service['estimated_time']); ?>" required>
                    </div>
                </div>

                <div style="margin-bottom:22px;">
                    <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                        Status
                    </label>
                    <select name="status" class="modern-input" required>
                        <option value="active" <?= $service['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $service['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div style="margin-bottom:26px;">
                    <label style="display:block;font-weight:900;color:#db2777;margin-bottom:10px;">
                        Deskripsi
                    </label>
                    <textarea name="description" rows="5" class="modern-input" required><?= htmlspecialchars($service['description']); ?></textarea>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" name="update" class="modern-btn">
                        Simpan Perubahan
                    </button>

                    <a href="services.php" class="modern-btn-outline">
                        Batal
                    </a>
                </div>

            </form>

        </div>

    </section>

</main>

<style>
@media (max-width:768px){
    form div[style*="grid-template-columns:1fr 1fr"]{
        grid-template-columns:1fr!important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>