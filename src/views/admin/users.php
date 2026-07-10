<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../public/login.php");
    exit;
}

$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

$where = "WHERE 1=1";

if ($roleFilter !== 'all') {
    $safeRole = mysqli_real_escape_string($conn, $roleFilter);
    $where .= " AND role='$safeRole'";
}

if ($statusFilter !== 'all') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND status='$safeStatus'";
}

$users = mysqli_query($conn, "
    SELECT *
    FROM users
    $where
    ORDER BY id DESC
");

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'] ?? 0;
$totalBuyer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='buyer'"))['total'] ?? 0;
$totalMitra = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='seller'"))['total'] ?? 0;
$totalCourier = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='courier'"))['total'] ?? 0;

function roleBadge($role)
{
    $styles = [
        'admin' => 'background:#fef3c7;color:#92400e;',
        'buyer' => 'background:#e0f2fe;color:#0369a1;',
        'seller' => 'background:#dcfce7;color:#166534;',
        'courier' => 'background:#dbeafe;color:#1d4ed8;',
    ];

    $labels = [
        'admin' => 'Admin',
        'buyer' => 'Pelanggan',
        'seller' => 'Mitra',
        'courier' => 'Kurir',
    ];

    return "<span class='status-pill' style='" . ($styles[$role] ?? 'background:#f1f5f9;color:#334155;') . "'>" . ($labels[$role] ?? ucfirst($role)) . "</span>";
}

function statusBadgeUser($status)
{
    if ($status === 'active') {
        return "<span class='status-pill' style='background:#dcfce7;color:#166534;'>Aktif</span>";
    }

    return "<span class='status-pill' style='background:#fee2e2;color:#b91c1c;'>Diblokir</span>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola User</title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>

<body class="soft-bg-pattern">

<?php include "../layouts/admin-sidebar.php"; ?>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<main class="dashboard-main">

    <?php include "../layouts/admin-topbar.php"; ?>

    <section style="padding:32px;">

        <div style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;flex-wrap:wrap;margin-bottom:28px;">
            <div>
                <p style="font-weight:900;color:#0284c7;margin-bottom:8px;">Admin Panel</p>
                <h1 class="page-title">Kelola User</h1>
                <p class="page-subtitle">Pantau semua akun admin, pelanggan, mitra, dan kurir.</p>
            </div>
        </div>

        <?php if (isset($_GET['updated'])) : ?>
            <div style="background:#dcfce7;color:#166534;border-radius:20px;padding:16px 20px;font-weight:900;margin-bottom:24px;">
                Status user berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])) : ?>
            <div style="background:#fee2e2;color:#b91c1c;border-radius:20px;padding:16px 20px;font-weight:900;margin-bottom:24px;">
                Gagal memperbarui user.
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;">
            <div class="modern-card" style="padding:24px;">
                <p style="color:#64748b;font-weight:800;">Total User</p>
                <h2 style="font-size:38px;font-weight:900;color:#0369a1;"><?= $totalUsers; ?></h2>
            </div>

            <div class="modern-card" style="padding:24px;">
                <p style="color:#64748b;font-weight:800;">Pelanggan</p>
                <h2 style="font-size:38px;font-weight:900;color:#0369a1;"><?= $totalBuyer; ?></h2>
            </div>

            <div class="modern-card" style="padding:24px;">
                <p style="color:#64748b;font-weight:800;">Mitra</p>
                <h2 style="font-size:38px;font-weight:900;color:#16a34a;"><?= $totalMitra; ?></h2>
            </div>

            <div class="modern-card" style="padding:24px;">
                <p style="color:#64748b;font-weight:800;">Kurir</p>
                <h2 style="font-size:38px;font-weight:900;color:#2563eb;"><?= $totalCourier; ?></h2>
            </div>
        </div>

        <div class="modern-card" style="padding:22px;margin-bottom:28px;">
            <form method="GET" style="display:grid;grid-template-columns:1fr 1fr auto;gap:16px;align-items:end;">
                <div>
                    <label style="display:block;font-weight:900;color:#0369a1;margin-bottom:9px;">Filter Role</label>
                    <select name="role" class="modern-input">
                        <option value="all" <?= $roleFilter == 'all' ? 'selected' : ''; ?>>Semua Role</option>
                        <option value="admin" <?= $roleFilter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="buyer" <?= $roleFilter == 'buyer' ? 'selected' : ''; ?>>Pelanggan</option>
                        <option value="seller" <?= $roleFilter == 'seller' ? 'selected' : ''; ?>>Mitra</option>
                        <option value="courier" <?= $roleFilter == 'courier' ? 'selected' : ''; ?>>Kurir</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-weight:900;color:#0369a1;margin-bottom:9px;">Filter Status</label>
                    <select name="status" class="modern-input">
                        <option value="all" <?= $statusFilter == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="active" <?= $statusFilter == 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="blocked" <?= $statusFilter == 'blocked' ? 'selected' : ''; ?>>Diblokir</option>
                    </select>
                </div>

                <button type="submit" class="modern-btn">Terapkan</button>
            </form>
        </div>

        <div class="modern-card" style="padding:26px;overflow:auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Kontak</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($users && mysqli_num_rows($users) > 0) : ?>

                        <?php while ($row = mysqli_fetch_assoc($users)) : ?>

                            <tr>
                                <td>
                                    <strong style="color:#0f172a;"><?= htmlspecialchars($row['name']); ?></strong>
                                    <p style="color:#64748b;margin-top:5px;"><?= htmlspecialchars($row['email']); ?></p>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['phone'] ?: '-'); ?>
                                    <p style="color:#64748b;margin-top:5px;max-width:260px;">
                                        <?= htmlspecialchars($row['address'] ?: '-'); ?>
                                    </p>
                                </td>

                                <td><?= roleBadge($row['role']); ?></td>

                                <td><?= statusBadgeUser($row['status']); ?></td>

                                <td>
                                    <?= !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-'; ?>
                                </td>

                                <td>
                                    <?php if ($row['id'] == $_SESSION['user']['id']) : ?>
                                        <span style="color:#64748b;font-weight:800;">Akun aktif</span>
                                    <?php else : ?>
                                        <form action="update-user-status.php" method="POST">
                                            <input type="hidden" name="user_id" value="<?= $row['id']; ?>">
                                            <input type="hidden" name="status" value="<?= $row['status'] == 'active' ? 'blocked' : 'active'; ?>">

                                            <button type="submit" class="<?= $row['status'] == 'active' ? 'modern-btn-outline' : 'modern-btn'; ?>" style="padding:10px 16px;">
                                                <?= $row['status'] == 'active' ? 'Blokir' : 'Aktifkan'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else : ?>

                        <tr>
                            <td colspan="6" style="text-align:center;color:#64748b;padding:34px;">
                                Data user tidak ditemukan.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<style>
@media (max-width: 1000px) {
    section div[style*="grid-template-columns:repeat(4,1fr)"],
    form[style*="grid-template-columns:1fr 1fr auto"] {
        grid-template-columns: repeat(2,1fr) !important;
    }
}

@media (max-width: 650px) {
    section div[style*="grid-template-columns:repeat(4,1fr)"],
    form[style*="grid-template-columns:1fr 1fr auto"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="../../assets/js/modern.js"></script>

</body>
</html>